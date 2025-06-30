<?php

namespace Mosparo\Controller\Api\V1\Verification;

use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Mosparo\ApiClient\RequestHelper;
use Mosparo\Helper\ProjectHelper;
use Mosparo\Helper\HmacSignatureHelper;
use Mosparo\Helper\SecurityHelper;
use Mosparo\Helper\StatisticHelper;
use Mosparo\Helper\VerificationHelper;
use Mosparo\Repository\SubmitTokenRepository;
use Mosparo\Util\TimeUtil;
use Mosparo\Verification\GeneralVerification;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/v1/verification')]
class VerificationApiController extends AbstractController
{
    protected ProjectHelper $projectHelper;

    protected HmacSignatureHelper $hmacSignatureHelper;

    protected VerificationHelper $verificationHelper;

    protected SecurityHelper $securityHelper;

    protected StatisticHelper $statisticHelper;

    public function __construct(ProjectHelper $projectHelper, HmacSignatureHelper $hmacSignatureHelper, VerificationHelper $verificationHelper, SecurityHelper $securityHelper, StatisticHelper $statisticHelper)
    {
        $this->projectHelper = $projectHelper;
        $this->hmacSignatureHelper = $hmacSignatureHelper;
        $this->verificationHelper = $verificationHelper;
        $this->securityHelper = $securityHelper;
        $this->statisticHelper = $statisticHelper;
    }

    #[Route('/verify', name: 'verification_api_verify')]
    public function verify(Request $request, EntityManagerInterface $entityManager, SubmitTokenRepository $submitTokenRepository): Response
    {
        // If there is no active project, we cannot do anything.
        if (!$this->projectHelper->hasActiveProject()) {
            return new JsonResponse(['error' => true, 'errorMessage' => 'No project available.']);
        }

        $activeProject = $this->projectHelper->getActiveProject();

        if (!$request->request->has('submitToken') || !$request->request->has('validationSignature') || !$request->request->has('formSignature')) {
            // Prepare the API debug data
            $debugInformation = [];
            if ($activeProject->isApiDebugMode()) {
                $debugInformation['debugInformation'] = [
                    'reason' => 'required_parameter_missing',
                    'hasSubmitToken' => $request->request->has('submitToken'),
                    'hasValidationSignature' => $request->request->has('validationSignature'),
                    'hasFormSignature' => $request->request->has('formSignature'),
                ];
            }

            return new JsonResponse(['error' => true, 'errorMessage' => 'Required parameter missing.'] + $debugInformation);
        }

        $submitToken = $submitTokenRepository->findOneBy(['token' => $request->request->get('submitToken')]);
        if ($submitToken === null || !$submitToken->isValid()) {
            // Prepare the API debug data
            $debugInformation = [];
            if ($activeProject->isApiDebugMode()) {
                if ($submitToken === null) {
                    $debugInformation['debugInformation'] = [
                        'reason' => 'submit_token_not_found',
                    ];
                } else if (!$submitToken->isValid()) {
                    $debugInformation['debugInformation'] = [
                        'reason' => 'submit_token_not_valid',
                    ];
                }
            }

            return new JsonResponse(['error' => true, 'errorMessage' => 'Submit token not found or not valid.'] + $debugInformation);
        }

        $submission = $submitToken->getLastSubmission();
        if (!$submission) {
            return new JsonResponse(['error' => true, 'errorMessage' => 'Submission does not exist.']);
        }

        $submitToken->setVerifiedAt(new DateTime());
        $submission->setVerifiedAt(new DateTime());

        // Get the client IP address
        $clientIpAddress = $submission->getDataValue('client', 'ipAddress');
        if (!$clientIpAddress) {
            $clientIpAddress = null;
        }

        // Determine the security settings
        $securitySettings = $this->securityHelper->determineSecuritySettings($clientIpAddress);

        // Check if the minimum time functionality is active and if the time difference is bigger than the minimum time.
        if ($securitySettings['minimumTimeActive']) {
            $minimumTimeSeconds = $securitySettings['minimumTimeSeconds'];
            $seconds = TimeUtil::getDifferenceInSeconds($submission->getSubmitToken()->getCreatedAt(), $submission->getVerifiedAt());

            $minimumTimeGv = new GeneralVerification(
                GeneralVerification::MINIMUM_TIME,
                ($seconds >= $minimumTimeSeconds),
                ['seconds' => $seconds, 'minimumTimeSeconds' => $minimumTimeSeconds]
            );
            $submission->addGeneralVerification($minimumTimeGv);

            if (!$minimumTimeGv->isValid()) {
                $submission->setValid($minimumTimeGv->isValid());

                $issue = [
                    'error' => true,
                    'errorMessage' => 'Verification failed.',
                    'debugInformation' => [
                        'reason' => 'minimum_time_invalid',
                        'minimumTimeExpected' => $minimumTimeSeconds,
                        'minimumTimeElapsed' => $seconds,
                    ],
                ];

                $submission->addIssue($issue);

                $entityManager->flush();

                if (!$activeProject->isApiDebugMode()) {
                    unset($issue['debugInformation']);
                }

                return new JsonResponse($issue);
            }
        }

        $validationSignature = $this->hmacSignatureHelper->createSignature($submission->getValidationToken(), $activeProject->getPrivateKey());
        if ($request->request->get('validationSignature') !== $validationSignature) {
            $submission->setValid(false);

            $issue = [
                'error' => true,
                'errorMessage' => 'Verification failed.',
                'debugInformation' => [
                    'reason' => 'validation_signature_invalid',
                    'expectedSignature' => $validationSignature,
                    'receivedSignature' => $request->request->get('validationSignature'),
                    'signaturePayload' => $submission->getValidationToken(),
                ],
            ];

            $submission->addIssue($issue);

            $entityManager->flush();

            if (!$activeProject->isApiDebugMode()) {
                unset($issue['debugInformation']);
            }

            return new JsonResponse($issue);
        }

        $requestData = $request->request->all();
        $formData = $requestData['formData'] ?? [];
        $verificationSignature = '';
        $verificationResult = $this->verificationHelper->verifyFormData($submission, $formData);
        if ($verificationResult['valid']) {
            $requestHelper = new RequestHelper($activeProject->getPublicKey(), $activeProject->getPrivateKey());
            $formSignature = $requestHelper->createFormDataHmacHash($formData);

            // Check for equal form submission, if the security feature is enabled
            if ($securitySettings['equalSubmissionsActive']) {
                $allowedNumberOfEqualSubmissions = $securitySettings['equalSubmissionsNumberOfEqualSubmissions'];
                $actualNumberOfEqualSubmissions = $this->securityHelper->countEqualSubmissions(
                    $formSignature,
                    $securitySettings['equalSubmissionsTimeFrame'],
                    $securitySettings['equalSubmissionsBasedOnIpAddress'],
                    $clientIpAddress
                );

                $equalSubmissionsGv = new GeneralVerification(
                    GeneralVerification::EQUAL_SUBMISSIONS,
                    ($actualNumberOfEqualSubmissions <= $allowedNumberOfEqualSubmissions),
                    ['allowed' => $allowedNumberOfEqualSubmissions, 'actual' => $actualNumberOfEqualSubmissions]
                );
                $submission->addGeneralVerification($equalSubmissionsGv);

                if (!$equalSubmissionsGv->isValid()) {
                    $submission->setValid($equalSubmissionsGv->isValid());

                    $issue = [
                        'error' => true,
                        'errorMessage' => 'Verification failed.',
                        'debugInformation' => [
                            'reason' => 'too_many_equal_submissions',
                            'allowedNumberOfEqualSubmissions' => $allowedNumberOfEqualSubmissions,
                            'actualNumberOfEqualSubmissions' => $actualNumberOfEqualSubmissions,
                        ],
                    ];

                    $submission->addIssue($issue);

                    $entityManager->flush();

                    if (!$activeProject->isApiDebugMode()) {
                        unset($issue['debugInformation']);
                    }

                    return new JsonResponse($issue);
                }
            }

            $validationSignature = $requestHelper->createHmacHash($submission->getValidationToken());
            $verificationSignature = $requestHelper->createHmacHash($validationSignature . $formSignature);

            $submission->setValid(true);
        } else {
            $submission->setValid(false);
        }

        $entityManager->flush();

        $this->statisticHelper->increaseDayStatistic($submission);

        return new JsonResponse([
            'valid' => $verificationResult['valid'],
            'verificationSignature' => $verificationSignature,
            'verifiedFields' => $verificationResult['verifiedFields'],
            'issues' => $verificationResult['issues'],
        ]);
    }
}