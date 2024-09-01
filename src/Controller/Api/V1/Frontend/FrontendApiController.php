<?php

namespace Mosparo\Controller\Api\V1\Frontend;

use DateTime;
use DateTimeInterface;
use Doctrine\ORM\EntityManagerInterface;
use Mosparo\ApiClient\RequestHelper;
use Mosparo\Entity\Delay;
use Mosparo\Entity\Lockout;
use Mosparo\Entity\Project;
use Mosparo\Entity\Submission;
use Mosparo\Entity\SubmitToken;
use Mosparo\Enum\LanguageSource;
use Mosparo\Helper\LocaleHelper;
use Mosparo\Helper\ProjectHelper;
use Mosparo\Helper\CleanupHelper;
use Mosparo\Helper\GeoIp2Helper;
use Mosparo\Helper\HmacSignatureHelper;
use Mosparo\Helper\RuleTesterHelper;
use Mosparo\Helper\SecurityHelper;
use Mosparo\Helper\StatisticHelper;
use Mosparo\Util\TokenGenerator;
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
        StatisticHelper $statisticHelper
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
    }

    #[Route('/request-submit-token', name: 'frontend_api_request_submit_token')]
    public function request(Request $request, EntityManagerInterface $entityManager): Response
    {
        // If there is no active project, we cannot do anything.
        if (!$this->projectHelper->hasActiveProject()) {
            return new JsonResponse(['error' => true, 'errorMessage' => 'No project available.']);
        }

        if (!$request->request->has('pageTitle') || !$request->request->has('pageUrl')) {
            return new JsonResponse(['error' => true, 'errorMessage' => 'Required parameters missing.']);
        }

        // Cleanup the database
        $this->cleanupHelper->cleanup();

        // Determine the security settings
        $securitySettings = $this->securityHelper->determineSecuritySettings($request->getClientIp());

        // Check if the request is allowed
        $securityResult = $this->securityHelper->checkIpAddress($request->getClientIp(), SecurityHelper::FEATURE_DELAY, $securitySettings);
        if ($securityResult instanceof Delay) {
            return $this->prepareSecurityResponse($request, $securityResult, true);
        }

        $submitToken = new SubmitToken();
        $submitToken->setIpAddress($request->getClientIp());
        $submitToken->setCreatedAt(new DateTime());
        $submitToken->setToken($this->tokenGenerator->generateToken());

        $submitToken->setPageTitle($request->request->get('pageTitle'));
        $submitToken->setPageUrl($request->request->get('pageUrl'));

        $entityManager->persist($submitToken);
        $entityManager->flush();

        $args = [];
        if ($securitySettings['honeypotFieldActive']) {
            $args['honeypotFieldName'] = $securitySettings['honeypotFieldName'];
        }

        return new JsonResponse([
            'submitToken' => $submitToken->getToken(),
            'messages' => $this->getTranslations($request),
            'invisible' => ($submitToken->getProject()->getDesignMode() === 'invisible-simple'),
            'showLogo' => $submitToken->getProject()->getConfigValue('showMosparoLogo') ?? true,
        ] + $args);
    }

    #[Route('/check-form-data', name: 'frontend_api_check_form_data')]
    public function checkFormData(Request $request, EntityManagerInterface $entityManager): Response
    {
        // If there is no active project, we cannot do anything.
        if (!$this->projectHelper->hasActiveProject()) {
            return new JsonResponse(['error' => true, 'errorMessage' => 'No project available.']);
        }

        // Cleanup the database
        $this->cleanupHelper->cleanup();

        // Determine the security settings
        $securitySettings = $this->securityHelper->determineSecuritySettings($request->getClientIp());

        // Check if the request is allowed
        $securityResult = $this->securityHelper->checkIpAddress($request->getClientIp(), SecurityHelper::FEATURE_LOCKOUT, $securitySettings);
        if ($securityResult instanceof Lockout) {
            return $this->prepareSecurityResponse($request, $securityResult);
        }

        $activeProject = $this->projectHelper->getActiveProject();

        if (!$request->request->has('submitToken')) {
            return new JsonResponse(['error' => true, 'errorMessage' => 'Submit token not set.']);
        }

        $submitTokenRepository = $entityManager->getRepository(SubmitToken::class);
        $submitToken = $submitTokenRepository->findOneBy([
            'token' => $request->request->get('submitToken'),
        ]);

        if ($submitToken === null || !$submitToken->isValid()) {
            return new JsonResponse(['error' => true, 'errorMessage' => 'Submit token not valid.']);
        }

        if (!$request->request->has('formData')) {
            return new JsonResponse(['error' => true, 'errorMessage' => 'Form data not set.']);
        }

        $formData = json_decode($request->request->get('formData'), true);
        if ($formData === null || !isset($formData['fields'])) {
            return new JsonResponse(['error' => true, 'errorMessage' => 'Form data not valid.']);
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
        $submission->setIgnoredFields($formData['ignoredFields']);

        // Check for the honeypot field
        if ($securitySettings['honeypotFieldActive']) {
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

        // Set the submission data
        $submission->setData([
            'formData' => $formData['fields'],
            'client' => $clientData
        ]);

        // Create signature
        $submission->setSignature($this->createSignature($submitToken, $formData['fields'], $activeProject));

        // Check the data
        if (!$submission->isSpam()) {
            $this->ruleTesterHelper->checkRequest($submission);
        }

        $submission->setSubmitToken($submitToken);

        $entityManager->persist($submission);
        $entityManager->flush();

        // Increase the day statistic if it is spam
        if ($submission->isSpam()) {
            $this->statisticHelper->increaseDayStatistic($submission);
        }

        return new JsonResponse([
            'valid' => (!$submission->isSpam()),
            'validationToken' => $submission->getValidationToken(),
        ]);
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

        return [
            'locale' => $usedLocale,
            'label' => $this->translator->trans('label', [], 'frontend'),

            'accessibilityCheckingData' => $this->translator->trans('accessibility.checkingData', [], 'frontend'),
            'accessibilityDataValid' => $this->translator->trans('accessibility.dataValid', [], 'frontend'),
            'accessibilityProtectedBy' => $this->translator->trans('accessibility.protectedBy', [], 'frontend'),

            'errorGotNoToken' => $this->translator->trans('error.gotNoToken', [], 'frontend'),
            'errorInternalError' => $this->translator->trans('error.internalError', [], 'frontend'),
            'errorNoSubmitTokenAvailable' => $this->translator->trans('error.noSubmitTokenAvailable', [], 'frontend'),
            'errorSpamDetected' => $this->translator->trans('error.spamDetected', [], 'frontend'),
            'errorLockedOut' => $this->translator->trans('error.lockedOut', [], 'frontend'),
            'errorDelay' => $this->translator->trans('error.delay', [], 'frontend'),

            'hpLeaveEmpty' => $this->translator->trans('hp.fieldTitle', [], 'frontend'),
        ];
    }

    protected function findCorrectLocales(Request $request): array
    {
        $project = $this->projectHelper->getActiveProject();
        $locales = ['en']; // The default locale, always fallback to this
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
            $htmlLocale = $request->request->get('htmlLanguage');
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

        return array_filter($locales);
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
}