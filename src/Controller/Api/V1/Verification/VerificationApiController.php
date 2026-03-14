<?php

namespace Mosparo\Controller\Api\V1\Verification;

use DateTime;
use DateTimeInterface;
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

        if ($activeProject->isMetadataAllowed() && $request->request->has('metadata')) {
            $metadata = json_decode($request->request->get('metadata'), true);
            if ($metadata) {
                $submission->appendData([
                    'metadata' => $metadata,
                ]);
            }
        }

        $responseMetadata = ($activeProject->isMetadataReturned()) ? ['metadata' => $submission->getData()['metadata'] ?? []] : [];

        // Get the client IP address
        $clientIpAddress = $submission->getDataValue('client', 'ipAddress');
        if (!$clientIpAddress) {
            $clientIpAddress = null;
        }

        // Determine the security settings
        $securitySettings = $this->securityHelper->determineSecuritySettings($clientIpAddress, $submitToken->getFormOriginData());

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

                $issue = array_merge([
                    'error' => true,
                    'errorMessage' => 'Verification failed.',
                    'debugInformation' => [
                        'reason' => 'minimum_time_invalid',
                        'minimumTimeExpected' => $minimumTimeSeconds,
                        'minimumTimeElapsed' => $seconds,
                    ],
                ], $responseMetadata);

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

            $issue = array_merge([
                'error' => true,
                'errorMessage' => 'Verification failed.',
                'debugInformation' => [
                    'reason' => 'validation_signature_invalid',
                    'expectedSignature' => $validationSignature,
                    'receivedSignature' => $request->request->get('validationSignature'),
                    'signaturePayload' => $submission->getValidationToken(),
                ],
            ], $responseMetadata);

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
            // Check for equal form submission, if the security feature is enabled
            if ($securitySettings['equalSubmissionsActive']) {
                $allowedNumberOfEqualSubmissions = $securitySettings['equalSubmissionsNumberOfEqualSubmissions'];
                $actualNumberOfEqualSubmissions = $this->securityHelper->countEqualSubmissions(
                    $submission->getSignature(),
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

                    $issue = array_merge([
                        'error' => true,
                        'errorMessage' => 'Verification failed.',
                        'debugInformation' => [
                            'reason' => 'too_many_equal_submissions',
                            'allowedNumberOfEqualSubmissions' => $allowedNumberOfEqualSubmissions,
                            'actualNumberOfEqualSubmissions' => $actualNumberOfEqualSubmissions,
                        ],
                    ], $responseMetadata);

                    $submission->addIssue($issue);

                    $entityManager->flush();

                    if (!$activeProject->isApiDebugMode()) {
                        unset($issue['debugInformation']);
                    }

                    return new JsonResponse($issue);
                }
            }

            $requestHelper = new RequestHelper($activeProject->getPublicKey(), $activeProject->getPrivateKey());
            $formSignature = $requestHelper->createFormDataHmacHash($formData);

            $validationSignature = $requestHelper->createHmacHash($submission->getValidationToken());
            $verificationSignature = $requestHelper->createHmacHash($validationSignature . $formSignature);

            // This logic ensures that even if silent mode is enabled, the submission cannot be valid if it is spam.
            $submission->setValid(!$submission->isSpam());
        } else {
            $submission->setValid(false);
        }

        $entityManager->flush();

        $this->statisticHelper->increaseDayStatistic($submission);

        $responseSpamData = [];
        if ($activeProject->isSpamDataReturned()) {
            $minimumTimeData = [];
            $minimumTimeGv = $submission->getGeneralVerification(GeneralVerification::MINIMUM_TIME);
            if ($minimumTimeGv) {
                $minimumTimeData['minimumTimeData'] = $minimumTimeGv->getData();
            }

            $equalSubmissionsData = [];
            $equalSubmissionsGv = $submission->getGeneralVerification(GeneralVerification::EQUAL_SUBMISSIONS);
            if ($equalSubmissionsGv) {
                $equalSubmissionsData['equalSubmissionsData'] = $equalSubmissionsGv->getData();
            }

            $responseSpamData['spamData'] = array_merge([
                'spamRating' => $submission->getSpamRating(),
                'spamDetectionRating' => $submission->getSpamDetectionRating(),
                'isSpam' => $submission->isSpam(),
                'isValid' => $submission->isValid(),
                'submittedAt' => $submission->getSubmittedAt()->format(DateTimeInterface::ATOM),
                'verifiedAt' => $submission->getVerifiedAt()->format(DateTimeInterface::ATOM),
            ], $minimumTimeData, $equalSubmissionsData);
        }

        return new JsonResponse(array_merge([
            'valid' => $submission->isValid(),
            'verificationSignature' => $verificationSignature,
            'verifiedFields' => $verificationResult['verifiedFields'],
            'issues' => $verificationResult['issues'],
        ], $responseMetadata, $responseSpamData));
    }

    #[Route('/store-metadata', name: 'verification_api_store_metadata')]
    public function storeMetadata(Request $request, EntityManagerInterface $entityManager, SubmitTokenRepository $submitTokenRepository): Response
    {
        // If there is no active project, we cannot do anything.
        if (!$this->projectHelper->hasActiveProject()) {
            return new JsonResponse(['error' => true, 'errorMessage' => 'No project available.']);
        }

        $activeProject = $this->projectHelper->getActiveProject();

        if (!$activeProject->isMetadataAllowed()) {
            return new JsonResponse(['error' => true, 'errorMessage' => 'Metadata is not allowed in this project.']);
        }

        if (!$request->request->has('submitToken') || !$request->request->has('validationSignature')) {
            // Prepare the API debug data
            $debugInformation = [];
            if ($activeProject->isApiDebugMode()) {
                $debugInformation['debugInformation'] = [
                    'reason' => 'required_parameter_missing',
                    'hasSubmitToken' => $request->request->has('submitToken'),
                    'hasValidationSignature' => $request->request->has('validationSignature'),
                ];
            }

            return new JsonResponse(['error' => true, 'errorMessage' => 'Required parameter missing.'] + $debugInformation);
        }

        $submitToken = $submitTokenRepository->findOneBy(['token' => $request->request->get('submitToken')]);
        if ($submitToken === null) {
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

        $validationSignature = $this->hmacSignatureHelper->createSignature($submission->getValidationToken(), $activeProject->getPrivateKey());
        if ($request->request->get('validationSignature') !== $validationSignature) {
            $responseData = [
                'error' => true,
                'errorMessage' => 'Validation signature invalid.',
            ];

            if ($activeProject->isApiDebugMode()) {
                $responseData['debugInformation'] = [
                    'reason' => 'validation_signature_invalid',
                    'expectedSignature' => $validationSignature,
                    'receivedSignature' => $request->request->get('validationSignature'),
                    'signaturePayload' => $submission->getValidationToken(),
                ];
            }

            return new JsonResponse($responseData);
        }

        $metadata = json_decode($request->request->get('metadata'), true);
        if ($metadata) {
            $submission->appendData([
                'metadata' => $metadata,
            ]);
        }

        $entityManager->flush();

        return new JsonResponse([
            'result' => true,
        ]);
    }
}