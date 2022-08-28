<?php

namespace Mosparo\Controller\Api\V1\Verification;

use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Mosparo\ApiClient\RequestHelper;
use Mosparo\Helper\ProjectHelper;
use Mosparo\Helper\HmacSignatureHelper;
use Mosparo\Helper\VerificationHelper;
use Mosparo\Repository\SubmitTokenRepository;
use Mosparo\Util\TimeUtil;
use Mosparo\Verification\GeneralVerification;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/api/v1/verification")
 */
class VerificationApiController extends AbstractController
{
    protected ProjectHelper $projectHelper;

    protected HmacSignatureHelper $hmacSignatureHelper;

    protected VerificationHelper $verificationHelper;

    public function __construct(ProjectHelper $projectHelper, HmacSignatureHelper $hmacSignatureHelper, VerificationHelper $verificationHelper)
    {
        $this->projectHelper = $projectHelper;
        $this->hmacSignatureHelper = $hmacSignatureHelper;
        $this->verificationHelper = $verificationHelper;
    }

    /**
     * @Route("/verify", name="verification_api_verify")
     */
    public function verify(Request $request, EntityManagerInterface $entityManager, SubmitTokenRepository $submitTokenRepository): Response
    {
        // If there is no active project, we cannot do anything.
        if (!$this->projectHelper->hasActiveProject()) {
            return new JsonResponse(['error' => true, 'errorMessage' => 'No project available.']);
        }

        $activeProject = $this->projectHelper->getActiveProject();

        if (!$request->request->has('submitToken') || !$request->request->has('validationSignature') || !$request->request->has('formSignature')) {
            return new JsonResponse(['error' => true, 'errorMessage' => 'Required parameter missing.']);
        }

        $submitToken = $submitTokenRepository->findOneBy(['token' => $request->request->get('submitToken')]);
        if ($submitToken === null || !$submitToken->isValid()) {
            return new JsonResponse(['error' => true, 'errorMessage' => 'Submit token not found or not valid.']);
        }

        $submission = $submitToken->getSubmission();
        if (!$submission) {
            return new JsonResponse(['error' => true, 'errorMessage' => 'Submission does not exist.']);
        }

        $submitToken->setVerifiedAt(new DateTime());
        $submission->setVerifiedAt(new DateTime());

        // Check if the minimum time functionality is active and if the time difference is bigger than the minimum time.
        if ($activeProject->getConfigValue('minimumTimeActive')) {
            $minimumTimeSeconds = $activeProject->getConfigValue('minimumTimeSeconds');
            $seconds = TimeUtil::getDifferenceInSeconds($submission->getSubmitToken()->getCreatedAt(), $submission->getVerifiedAt());

            $minimumTimeGv = new GeneralVerification(
                GeneralVerification::MINIMUM_TIME,
                ($seconds >= $minimumTimeSeconds),
                ['seconds' => $seconds, 'minimumTimeSeconds' => $minimumTimeSeconds]
            );
            $submission->addGeneralVerification($minimumTimeGv);

            if (!$minimumTimeGv->isValid()) {
                $submission->setValid($minimumTimeGv->isValid());

                $entityManager->flush();

                return new JsonResponse(['error' => true, 'errorMessage' => 'Validation failed.']);
            }
        }

        $validationSignature = $this->hmacSignatureHelper->createSignature($submission->getValidationToken(), $activeProject->getPrivateKey());
        if ($request->request->get('validationSignature') !== $validationSignature) {
            $submission->setValid(false);
            $entityManager->flush();

            return new JsonResponse(['error' => true, 'errorMessage' => 'Validation failed.']);
        }

        $formData = (array) $request->request->get('formData');
        $verificationSignature = '';
        $verificationResult = $this->verificationHelper->verifyFormData($submission, $formData);
        if ($verificationResult['valid']) {
            $requestHelper = new RequestHelper($activeProject->getPublicKey(), $activeProject->getPrivateKey());
            $formSignature = $requestHelper->createFormDataHmacHash($formData);

            $validationSignature = $requestHelper->createHmacHash($submission->getValidationToken());
            $verificationSignature = $requestHelper->createHmacHash($validationSignature . $formSignature);

            $submission->setValid(true);
        } else {
            $submission->setValid(false);
        }

        $entityManager->flush();

        return new JsonResponse([
            'valid' => $verificationResult['valid'],
            'verificationSignature' => $verificationSignature,
            'verifiedFields' => $verificationResult['verifiedFields'],
            'issues' => $verificationResult['issues'],
        ]);
    }
}