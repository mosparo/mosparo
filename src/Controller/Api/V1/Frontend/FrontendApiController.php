<?php

namespace Mosparo\Controller\Api\V1\Frontend;

use DateTime;
use DateTimeInterface;
use Doctrine\ORM\EntityManagerInterface;
use Mosparo\Entity\Delay;
use Mosparo\Entity\Lockout;
use Mosparo\Entity\Project;
use Mosparo\Entity\Submission;
use Mosparo\Entity\SubmitToken;
use Mosparo\Helper\ProjectHelper;
use Mosparo\Helper\CleanupHelper;
use Mosparo\Helper\GeoIp2Helper;
use Mosparo\Helper\HmacSignatureHelper;
use Mosparo\Helper\RuleTesterHelper;
use Mosparo\Helper\SecurityHelper;
use Mosparo\Util\TokenGenerator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\LocaleAwareInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @Route("/api/v1/frontend")
 */
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

    public function __construct(
        ProjectHelper $projectHelper,
        TokenGenerator $tokenGenerator,
        RuleTesterHelper $ruleTesterHelper,
        HmacSignatureHelper $hmacSignatureHelper,
        SecurityHelper $securityHelper,
        CleanupHelper $cleanupHelper,
        GeoIp2Helper $geoIp2Helper,
        TranslatorInterface $translator
    ) {
        $this->projectHelper = $projectHelper;
        $this->tokenGenerator = $tokenGenerator;
        $this->ruleTesterHelper = $ruleTesterHelper;
        $this->hmacSignatureHelper = $hmacSignatureHelper;
        $this->securityHelper = $securityHelper;
        $this->cleanupHelper = $cleanupHelper;
        $this->geoIp2Helper = $geoIp2Helper;
        $this->translator = $translator;
    }

    /**
     * @Route("/request-submit-token", name="frontend_api_request_submit_token")
     */
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

        // Check if the request is allowed
        $securityResult = $this->securityHelper->checkIpAddress($request->getClientIp());
        if ($securityResult instanceof Lockout || $securityResult instanceof Delay) {
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
        if ($submitToken->getProject()->getConfigValue('honeypotFieldActive')) {
            $args['honeypotFieldName'] = $submitToken->getProject()->getConfigValue('honeypotFieldName');
        }

        return new JsonResponse([
            'submitToken' => $submitToken->getToken(),
            'messages' => $this->getTranslations($request),
        ] + $args);
    }

    /**
     * @Route("/check-form-data", name="frontend_api_check_form_data")
     */
    public function checkFormData(Request $request, EntityManagerInterface $entityManager): Response
    {
        // If there is no active project, we cannot do anything.
        if (!$this->projectHelper->hasActiveProject()) {
            return new JsonResponse(['error' => true, 'errorMessage' => 'No project available.']);
        }

        // Cleanup the database
        $this->cleanupHelper->cleanup();

        // Check if the request is allowed
        $securityResult = $this->securityHelper->checkIpAddress($request->getClientIp());
        if ($securityResult instanceof Lockout || $securityResult instanceof Delay) {
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
        $submission->setSubmitToken($submitToken);
        $submission->setSubmittedAt(new DateTime());
        $submission->setIgnoredFields($formData['ignoredFields']);

        // Check for the honeypot field
        if ($submitToken->getProject()->getConfigValue('honeypotFieldActive')) {
            $hpFieldName = $activeProject->getConfigValue('honeypotFieldName');
            $hpField = false;

            foreach ($formData['fields'] as $key => $field) {
                if ($field['name'] === $hpFieldName) {
                    $hpField = $field;
                    $formData['fields'][$key]['type'] = 'honeypot';
                    break;
                }
            }

            if ($hpField['value'] != '') {
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

        $entityManager->persist($submission);
        $entityManager->flush();

        return new JsonResponse(['valid' => (!$submission->isSpam()), 'validationToken' => $submission->getValidationToken()]);
    }

    protected function createSignature(SubmitToken $submitToken, $formData, Project $activeProject): string
    {
        $payload = $this->hmacSignatureHelper->prepareData($this->createFormStructure($formData))
                 . $submitToken->getToken();

        return $this->hmacSignatureHelper->createSignature($payload, $activeProject->getPrivateKey());
    }

    protected function createFormStructure(array $data): array
    {
        $formData = [];
        foreach ($data as $field) {
            if (isset($field['type']) && $field['type'] == 'honeypot') {
                continue;
            }

            $formData[$field['name']] = $field['value'];
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

        return new JsonResponse($data);
    }

    protected function getTranslations(Request $request): array
    {
        if ($this->translator instanceof LocaleAwareInterface && $request->getPreferredLanguage()) {
            $this->translator->setLocale($request->getPreferredLanguage());
        }

        return [
            'label' => $this->translator->trans('label', [], 'frontend'),

            'accessibilityCheckingData' => $this->translator->trans('accessibility.checkingData', [], 'frontend'),
            'accessibilityDataValid' => $this->translator->trans('accessibility.dataValid', [], 'frontend'),

            'errorGotNoToken' => $this->translator->trans('error.gotNoToken', [], 'frontend'),
            'errorInternalError' => $this->translator->trans('error.internalError', [], 'frontend'),
            'errorNoSubmitTokenAvailable' => $this->translator->trans('error.noSubmitTokenAvailable', [], 'frontend'),
            'errorSpamDetected' => $this->translator->trans('error.spamDetected', [], 'frontend'),
            'errorLockedOut' => $this->translator->trans('error.lockedOut', [], 'frontend'),
            'errorDelay' => $this->translator->trans('error.delay', [], 'frontend'),

            'hpLeaveEmpty' => $this->translator->trans('hp.fieldTitle', [], 'frontend'),
        ];
    }
}