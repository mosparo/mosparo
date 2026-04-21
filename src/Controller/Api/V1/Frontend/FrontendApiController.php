<?php

namespace Mosparo\Controller\Api\V1\Frontend;

use DateTime;
use DateTimeInterface;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\ORM\EntityManagerInterface;
use Mosparo\ApiClient\RequestHelper;
use Mosparo\Attributes\TranslationKeyInfo;
use Mosparo\Entity\Delay;
use Mosparo\Entity\Lockout;
use Mosparo\Entity\PartialSubmission;
use Mosparo\Entity\Project;
use Mosparo\Entity\Submission;
use Mosparo\Entity\SubmitToken;
use Mosparo\Entity\Translation;
use Mosparo\Enum\CleanupExecutor;
use Mosparo\Enum\LanguageSource;
use Mosparo\Enum\TranslationKey;
use Mosparo\Helper\LocaleHelper;
use Mosparo\Helper\ProjectHelper;
use Mosparo\Helper\CleanupHelper;
use Mosparo\Helper\GeoIp2Helper;
use Mosparo\Helper\HmacSignatureHelper;
use Mosparo\Helper\RuleTesterHelper;
use Mosparo\Helper\SecurityHelper;
use Mosparo\Helper\StatisticHelper;
use Mosparo\Util\IpUtil;
use Mosparo\Util\TokenGenerator;
use Mosparo\Verification\GeneralVerification;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\LocaleAwareInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('/api/v1/frontend')]
class FrontendApiController extends AbstractController
{
    protected ProjectHelper $projectHelper;

    protected TokenGenerator $tokenGenerator;

    protected RuleTesterHelper $ruleTesterHelper;

    protected HmacSignatureHelper $hmacSignatureHelper;

    protected SecurityHelper $securityHelper;

    protected CleanupHelper $cleanupHelper;

    protected GeoIp2Helper $geoIp2Helper;

    protected TranslatorInterface $translator;

    protected LocaleHelper $localeHelper;

    protected StatisticHelper $statisticHelper;

    protected EntityManagerInterface $entityManager;

    public function __construct(
        ProjectHelper $projectHelper,
        TokenGenerator $tokenGenerator,
        RuleTesterHelper $ruleTesterHelper,
        HmacSignatureHelper $hmacSignatureHelper,
        SecurityHelper $securityHelper,
        CleanupHelper $cleanupHelper,
        GeoIp2Helper $geoIp2Helper,
        TranslatorInterface $translator,
        LocaleHelper $localeHelper,
        StatisticHelper $statisticHelper,
        EntityManagerInterface $entityManager
    ) {
        $this->projectHelper = $projectHelper;
        $this->tokenGenerator = $tokenGenerator;
        $this->ruleTesterHelper = $ruleTesterHelper;
        $this->hmacSignatureHelper = $hmacSignatureHelper;
        $this->securityHelper = $securityHelper;
        $this->cleanupHelper = $cleanupHelper;
        $this->geoIp2Helper = $geoIp2Helper;
        $this->translator = $translator;
        $this->localeHelper = $localeHelper;
        $this->statisticHelper = $statisticHelper;
        $this->entityManager = $entityManager;
    }

    #[Route('/request-submit-token', name: 'frontend_api_request_submit_token')]
    public function request(Request $request): Response
    {
        // If there is no active project, we cannot do anything.
        if (!$this->projectHelper->hasActiveProject()) {
            return new JsonResponse(['error' => true, 'errorMessage' => 'No project available.']);
        }

        if (!$request->request->has('pageTitle') || !$request->request->has('pageUrl') || !$request->request->has('formActionUrl')) {
            return new JsonResponse(['error' => true, 'errorMessage' => 'Required parameters missing.']);
        }

        // Cleanup the database
        $this->cleanupHelper->cleanup(cleanupExecutor: CleanupExecutor::FRONTEND_API);

        // Determine the security settings
        $securitySettings = $this->securityHelper->determineSecuritySettings($request->getClientIp(), [
            'pageUrl' => $request->request->get('pageUrl'),
            'formActionUrl' => $request->request->get('formActionUrl'),
            'formId' => $request->request->get('formId'),
        ]);

        // Check if the request is allowed
        $securityResult = $this->securityHelper->checkIpAddress($request->getClientIp(), SecurityHelper::FEATURE_DELAY, $securitySettings);
        if ($securityResult instanceof Delay) {
            return $this->prepareSecurityResponse($request, $securityResult, true);
        }

        $isIpOnAllowList = $this->isIpOnAllowList($request->getClientIp(), $securitySettings);

        $submitToken = null;
        if ($request->request->has('submitToken') && $request->request->get('submitToken')) {
            $token = $request->request->get('submitToken');
            $submitTokenRepository = $this->entityManager->getRepository(SubmitToken::class);
            $submitToken = $submitTokenRepository->findOneBy(['token' => $token]);

            if ($submitToken === null || !$submitToken->isValid()) {
                return new JsonResponse(['error' => true, 'errorMessage' => 'The given submit token does not exist or has already been used.']);
            }
        }

        // Create a new submit token
        if (!$submitToken) {
            $submitToken = new SubmitToken();
            $submitToken->setIpAddress($request->getClientIp());
            $submitToken->setCreatedAt(new DateTime());
            $submitToken->setToken($this->tokenGenerator->generateToken());

            $submitToken->setPageTitle($request->request->get('pageTitle'));
            $submitToken->setPageUrl($request->request->get('pageUrl'));
            $submitToken->setFormActionUrl($request->request->get('formActionUrl'));
            $submitToken->setFormId($request->request->get('formId'));

            $this->entityManager->persist($submitToken);
        }

        $args = [];
        if ($securitySettings['honeypotFieldActive'] && !$isIpOnAllowList) {
            $args['honeypotFieldName'] = $securitySettings['honeypotFieldName'];
        }

        if ($securitySettings['proofOfWorkActive'] && !$isIpOnAllowList) {
            $maxNumber = $this->findMaximumNumberForProofOfWorkRange($request, $securitySettings);
            $number = mt_rand(1, $maxNumber);

            $proofOfWorkResult = hash('sha256', $submitToken->gettoken() . $number);
            $submitToken->setProofOfWorkResult($proofOfWorkResult);

            $args['proofOfWorkResult'] = $proofOfWorkResult;
            $args['proofOfWorkMaxNumber'] = $maxNumber;
        }

        $this->entityManager->flush();

        return new JsonResponse([
            'submitToken' => $submitToken->getToken(),
            'messages' => $this->getTranslations($request),
            'invisible' => ($submitToken->getProject()->getDesignMode() === 'invisible-simple'),
            'showLogo' => $submitToken->getProject()->getConfigValue('showMosparoLogo') ?? true,
        ] + $args);
    }

    #[Route('/store-form-data', name: 'frontend_api_store_form_data')]
    public function storeFormData(Request $request): Response
    {
        [$submitToken, $response] = $this->validateRequest($request);
        if ($response !== null) {
            return $response;
        }

        if (!$request->request->has('formData') && !$request->request->has('metadata')) {
            return new JsonResponse(['error' => true, 'errorMessage' => 'Form data and metadata not set.']);
        }

        $activeProject = $this->projectHelper->getActiveProject();

        $partialSubmission = $submitToken->getPartialSubmission();
        if (!$partialSubmission) {
            // Create the partial submission
            $partialSubmission = new PartialSubmission();
            $partialSubmission->setSubmitToken($submitToken);

            $this->entityManager->persist($partialSubmission);
        }

        $formData = [];
        if ($request->request->has('formData')) {
            $formData = json_decode($request->request->get('formData'), true);
            if ($formData === null || !isset($formData['fields'])) {
                return new JsonResponse(['error' => true, 'errorMessage' => 'Form data not valid.']);
            }

            if (isset($formData['ignoredFields']) && $formData['ignoredFields']) {
                $partialSubmission->appendIgnoredFields($formData['ignoredFields']);
            }
        }

        $metadata = [];
        if ($activeProject->isMetadataAllowed() && $request->request->has('metadata')) {
            $metadata['metadata'] = json_decode($request->request->get('metadata'), true);
        }

        $partialSubmission->appendData(array_merge([
            'formData' => $formData['fields'],
        ], $metadata));

        $partialSubmission->setUpdatedAt(new DateTime());

        $this->entityManager->flush();

        return new JsonResponse([
            'result' => true,
        ]);
    }

    #[Route('/check-form-data', name: 'frontend_api_check_form_data')]
    public function checkFormData(Request $request): Response
    {
        [$submitToken, $response] = $this->validateRequest($request);
        if ($response !== null) {
            return $response;
        }

        // Determine the security settings
        $securitySettings = $this->securityHelper->determineSecuritySettings($request->getClientIp(), $submitToken->getFormOriginData());

        // Check if the request is allowed
        $securityResult = $this->securityHelper->checkIpAddress($request->getClientIp(), SecurityHelper::FEATURE_LOCKOUT, $securitySettings);
        if ($securityResult instanceof Lockout) {
            return $this->prepareSecurityResponse($request, $securityResult);
        }

        $isIpOnAllowList = $this->isIpOnAllowList($request->getClientIp(), $securitySettings);

        $activeProject = $this->projectHelper->getActiveProject();

        if (!$request->request->has('formData')) {
            return new JsonResponse(['error' => true, 'errorMessage' => 'Form data not set.']);
        }

        $formData = json_decode($request->request->get('formData'), true);
        if ($formData === null || !isset($formData['fields'])) {
            return new JsonResponse(['error' => true, 'errorMessage' => 'Form data not valid.']);
        }

        $metadata = [];
        if ($activeProject->isMetadataAllowed() && $request->request->has('metadata')) {
            $metadata['metadata'] = json_decode($request->request->get('metadata'), true);
        }

        // Add the client data
        $clientData = [
            [
                'name' => 'ipAddress',
                'value' => $request->getClientIp(),
                'fieldPath' => 'ipAddress'
            ],
            [
                'name' => 'userAgent',
                'value' => $request->headers->get('User-Agent', null),
                'fieldPath' => 'userAgent'
            ]
        ];
        $ipLocalization = $this->geoIp2Helper->locateIpAddress($request->getClientIp());
        if ($ipLocalization !== false) {
            $clientData[] = [
                'name' => 'asNumber',
                'value' => $ipLocalization->getAsNumber(),
                'fieldPath' => 'asNumber'
            ];
            $clientData[] = [
                'name' => 'asOrganization',
                'value' => $ipLocalization->getAsOrganization(),
                'fieldPath' => 'asOrganization'
            ];
            $clientData[] = [
                'name' => 'country',
                'value' => $ipLocalization->getCountry(),
                'fieldPath' => 'country'
            ];
        }

        // Create the submission
        $submission = new Submission();
        $submission->setSubmittedAt(new DateTime());

        // Add the content from the partial submission, if available.
        if ($submitToken->getPartialSubmission()) {
            $partialSubmission = $submitToken->getPartialSubmission();
            $submission->appendData($partialSubmission->getData());
            $submission->setIgnoredFields(array_merge($partialSubmission->getIgnoredFields(), $formData['ignoredFields']));
        } else {
            $submission->setIgnoredFields($formData['ignoredFields']);
        }

        // Check for the honeypot field
        if ($securitySettings['honeypotFieldActive'] && !$isIpOnAllowList) {
            $hpFieldName = $securitySettings['honeypotFieldName'];
            $hpField = false;

            foreach ($formData['fields'] as $key => $field) {
                if ($field['name'] === $hpFieldName) {
                    $hpField = $field;
                    $formData['fields'][$key]['type'] = 'honeypot';
                    break;
                }
            }

            if (is_array($hpField) && $hpField['value'] != '') {
                $submission->setSpamRating($activeProject->getSpamScore() + 1);
                $submission->setSpamDetectionRating($activeProject->getSpamScore());
                $submission->setSpam(true);

                $matchedRuleItems = [
                    'formData.' . $hpField['fieldPath'] => [[
                        'type' => 'honeypot',
                        'value' => '',
                        'rating' => $submission->getSpamRating(),
                        'uuid' => ''
                    ]
                ]];
                $submission->setMatchedRuleItems($matchedRuleItems);
            }
        }

        // Check the proof of work result
        if ($securitySettings['proofOfWorkActive'] && !$isIpOnAllowList) {
            $number = intval($request->request->get('proofOfWorkNumber', 0));
            $proofOfWorkResult = hash('sha256', $submitToken->gettoken() . $number);

            $proofOfWorkGv = new GeneralVerification(
                GeneralVerification::PROOF_OF_WORK,
                ($submitToken->getProofOfWorkResult() === $proofOfWorkResult),
                ['expectedHash' => $submitToken->getProofOfWorkResult(), 'generatedHash' => $proofOfWorkResult]
            );
            $submission->addGeneralVerification($proofOfWorkGv);

            if (!$proofOfWorkGv->isValid()) {
                $submission->setSpamRating($activeProject->getSpamScore() + 1);
                $submission->setSpamDetectionRating($activeProject->getSpamScore());
                $submission->setSpam(true);
            }
        }

        // Set the submission data
        $submission->appendData(array_merge([
            'formData' => $formData['fields'],
            'client' => $clientData,
        ], $metadata));

        // Create signature
        $submission->setSignature($this->createSignature($submitToken, $formData['fields'], $activeProject));

        // Check the data. If the silent mode is enabled, check every request.
        if (!$submission->isSpam() || $activeProject->isSilentModeEnabled()) {
            $this->ruleTesterHelper->checkRequest($submission, $securitySettings);
        }

        $submission->setSubmitToken($submitToken, true);

        $this->entityManager->persist($submission);
        $this->entityManager->flush();

        // Make a second transaction and set the last submission to prevent deadlocks on the insert transaction above.
        $submitToken->setLastSubmission($submission);
        $this->entityManager->flush();

        // Increase the day statistic if it is spam. We count anyway, even if the silent mode is enabled
        if ($submission->isSpam()) {
            $this->statisticHelper->increaseDayStatisticForSubmission($submission);
        }

        return new JsonResponse([
            'valid' => (!$submission->isSpam() || $activeProject->isSilentModeEnabled()),
            'validationToken' => $submission->getValidationToken(),
        ]);
    }

    protected function validateRequest(Request $request): array
    {
        // If there is no active project, we cannot do anything.
        if (!$this->projectHelper->hasActiveProject()) {
            return [null, new JsonResponse(['error' => true, 'errorMessage' => 'No project available.'])];
        }

        // Cleanup the database
        $this->cleanupHelper->cleanup(cleanupExecutor: CleanupExecutor::FRONTEND_API);

        if (!$request->request->has('submitToken')) {
            return [null, new JsonResponse(['error' => true, 'errorMessage' => 'Submit token not set.'])];
        }

        $submitTokenRepository = $this->entityManager->getRepository(SubmitToken::class);
        $submitToken = $submitTokenRepository->findOneBy([
            'token' => $request->request->get('submitToken'),
        ]);

        if ($submitToken === null || !$submitToken->isValid()) {
            return [null, new JsonResponse(['error' => true, 'errorMessage' => 'Submit token not valid.'])];
        }

        return [$submitToken, null];
    }

    protected function createSignature(SubmitToken $submitToken, $formData, Project $activeProject): string
    {
        $requestHelper = new RequestHelper($activeProject->getPublicKey(), $activeProject->getPrivateKey());
        $formData = $requestHelper->prepareFormData($this->createFormStructure($formData));

        return $requestHelper->createFormDataHmacHash($formData);
    }

    protected function createFormStructure(array $data): array
    {
        $formData = [];
        foreach ($data as $field) {
            if (isset($field['type']) && $field['type'] == 'honeypot') {
                continue;
            }

            $formData[$field['name']] = $field['value'] ?? '';
        }

        return $formData;
    }

    protected function prepareSecurityResponse(Request $request, $result, $withMessages = false): Response
    {
        $data = [];
        if ($result instanceof Lockout) {
            $data = [
                'security' => true,
                'type' => 'lockout',
                'until' => $result->getValidUntil()->format(DateTimeInterface::ATOM),
            ];
        } else if ($result instanceof Delay) {
            $data = [
                'security' => true,
                'type' => 'delay',
                'forSeconds' => $result->getDuration(),
                'now' => (new DateTime())->format(DateTimeInterface::ATOM)
            ];
        }

        if ($withMessages) {
            $data['messages'] = $this->getTranslations($request);
        }

        if ($this->projectHelper->getActiveProject() && $this->projectHelper->getActiveProject()->getDesignMode() === 'invisible-simple') {
            $data['invisible'] = true;
        }

        return new JsonResponse($data);
    }

    protected function getTranslations(Request $request): array
    {
        $usedLocale = 'en';
        if ($this->translator instanceof LocaleAwareInterface) {
            $locales = $this->findCorrectLocales($request);

            foreach ($locales as $locale) {
                $this->translator->setLocale($locale);

                // Try to determine the locale for which we will return the messages. If we don't have the translation
                // for a locale, mosparo falls back to English.
                $catalogue = $this->translator->getCatalogue($locale);

                if ($catalogue->defines('label', 'frontend')) {
                    $usedLocale = $locale;
                    break;
                }
            }
        }

        $projectTranslations = $this->getProjectTranslations($locales);

        $label = null;
        if (isset($projectTranslations[TranslationKey::LABEL->name])) {
            $labelTranslation = current($projectTranslations[TranslationKey::LABEL->name]);

            $label = $labelTranslation->getText();
            $usedLocale = $labelTranslation->getLocale();
        }

        return [
            'locale' => $usedLocale,
            'label' => $label ?? $this->translator->trans('label', [], 'frontend'),

            'accessibilityCheckingData' => $this->getTranslation($projectTranslations, TranslationKey::ACCESSIBILITY_CHECKING_DATA),
            'accessibilityDataValid' => $this->getTranslation($projectTranslations, TranslationKey::ACCESSIBILITY_DATA_VALID),
            'accessibilityProtectedBy' => $this->getTranslation($projectTranslations, TranslationKey::ACCESSIBILITY_PROTECTED_BY),

            'errorGotNoToken' => $this->getTranslation($projectTranslations, TranslationKey::ERROR_GOT_NO_TOKEN),
            'errorInternalError' => $this->getTranslation($projectTranslations, TranslationKey::ERROR_INTERNAL_ERROR),
            'errorNoSubmitTokenAvailable' => $this->getTranslation($projectTranslations, TranslationKey::ERROR_NO_SUBMIT_TOKEN_AVAILABLE),
            'errorSpamDetected' => $this->getTranslation($projectTranslations, TranslationKey::ERROR_SPAM_DETECTED),
            'errorLockedOut' => $this->getTranslation($projectTranslations, TranslationKey::ERROR_LOCKED_OUT),
            'errorDelay' => $this->getTranslation($projectTranslations, TranslationKey::ERROR_DELAY),

            'hpLeaveEmpty' => $this->getTranslation($projectTranslations, TranslationKey::HONEY_POT_FIELD_TITLE),
        ];
    }

    protected function getProjectTranslations(array $locales)
    {
        $qb = $this->entityManager->createQueryBuilder()
            ->select('t')
            ->from(Translation::class, 't')
            ->where('t.locale IN (:locales)')
            ->andWhere('t.project = :project')
            ->setParameter('locales', $locales, ArrayParameterType::STRING)
            ->setParameter('project', $this->projectHelper->getActiveProject())
        ;

        $translations = [];
        foreach ($qb->getQuery()->toIterable() as $translation) {
            $key = $translation->getTranslationKey()->name;
            if (!isset($translations[$key])) {
                $translations[$key] = [];
            }

            $translations[$key][$translation->getLocale()] = $translation;
        }

        // Sort the translations by the order of the locales. This should always
        // be the correct priority to pick the correct translation.
        $sortedTranslations = [];
        foreach ($translations as $key => $translationsForKey) {
            $sortedTranslations[$key] = [];

            foreach ($locales as $locale) {
                if (isset($translationsForKey[$locale])) {
                    $sortedTranslations[$key][] = $translationsForKey[$locale];
                }
            }
        }

        return $sortedTranslations;
    }

    protected function getTranslation(array $translations, TranslationKey $translationKey): string
    {
        $key = $translationKey->name;
        if (isset($translations[$key]) && $translations[$key]) {
            return current($translations[$key])->getText();
        }

        $info = TranslationKeyInfo::from($translationKey);
        return $this->translator->trans($info->frontendKey, [], 'frontend');
    }

    protected function findCorrectLocales(Request $request): array
    {
        $project = $this->projectHelper->getActiveProject();
        $locales = [];
        $browserLocale = null;
        $staticLocale = null;
        $htmlLocale = null;

        if ($request->getPreferredLanguage()) {
            $browserLocale = $this->localeHelper->fixPreferredLanguage($request->getPreferredLanguage());
        }

        if ($request->request->has('language') && $request->request->get('language')) {
            $staticLocale = $this->localeHelper->fixPreferredLanguage($request->request->get('language'));
        }

        if ($request->request->has('htmlLanguage') && $request->request->get('htmlLanguage')) {
            $htmlLocale = str_replace('-', '_', $request->request->get('htmlLanguage'));
        }

        if ($staticLocale) {
            $this->addLocales($locales, $staticLocale);
        } else if ($project->getLanguageSource() === LanguageSource::BROWSER_FALLBACK) {
            $this->addLocales($locales, $browserLocale);
        } else if ($project->getLanguageSource() === LanguageSource::BROWSER_HTML_FALLBACK) {
            $this->addLocales($locales, $htmlLocale);
            $this->addLocales($locales, $browserLocale);
        } else if ($project->getLanguageSource() === LanguageSource::HTML_BROWSER_FALLBACK) {
            $this->addLocales($locales, $browserLocale);
            $this->addLocales($locales, $htmlLocale);
        }

        $locales[] = 'en'; // The default locale, always fallback to this

        return array_unique(array_filter($locales));
    }

    protected function addLocales(&$locales, $locale)
    {
        if (strlen($locale) > 2) {
            $fallbackLocale = substr($locale, 0, 2);

            if (!in_array($fallbackLocale, $locales)) {
                array_unshift($locales, $fallbackLocale);
            }
        }

        if (!in_array($locale, $locales)) {
            array_unshift($locales, $locale);
        }
    }

    protected function isIpOnAllowList(string $ipAddress, array $securitySettings)
    {
        return trim($securitySettings['ipAllowList']) && IpUtil::isIpAllowed($ipAddress, $securitySettings['ipAllowList']);
    }

    protected function findMaximumNumberForProofOfWorkRange(Request $request, array $securitySettings): int
    {
        $normalMaxNumber = (10 ** $securitySettings['proofOfWorkComplexity']) - 1;

        if (!$securitySettings['proofOfWorkDynamicComplexityActive']) {
            return $normalMaxNumber;
        }

        $dcMaxNumber = (10 ** $securitySettings['proofOfWorkDynamicComplexityMaxComplexity']) - 1;
        $delta = $dcMaxNumber - $normalMaxNumber;

        $numberOfMaxSubmissions = $securitySettings['proofOfWorkDynamicComplexityNumberOfSubmissions'];

        if ($securitySettings['proofOfWorkDynamicComplexityBasedOnIpAddress']) {
            $actualNumberOfSubmissions = $this->securityHelper->countRequests(
                $request->getClientIp(),
                $securitySettings['proofOfWorkDynamicComplexityTimeFrame']
            );
        } else {
            $actualNumberOfSubmissions = $this->securityHelper->countRequestsInTimeFrame(
                $securitySettings['proofOfWorkDynamicComplexityTimeFrame'],
            );
        }

        $percentage = (100 / $numberOfMaxSubmissions) * $actualNumberOfSubmissions;
        if ($percentage > 100) {
            $percentage = 100;
        }

        return $normalMaxNumber + (($delta / 100) * $percentage);
    }
}