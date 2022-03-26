<?php

namespace Mosparo\Controller\Api\V1\Verification;

use DateTime;
use Mosparo\Helper\ProjectHelper;
use Mosparo\Helper\HmacSignatureHelper;
use Mosparo\Repository\SubmitTokenRepository;
use Mosparo\Util\TimeUtil;
use Mosparo\Verification\GeneralVerification;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/api/v1/verification")
 */
class VerificationApiController extends AbstractController
{
    protected $projectHelper;

    protected $hmacSignatureHelper;

    public function __construct(ProjectHelper $projectHelper, HmacSignatureHelper $hmacSignatureHelper)
    {
        $this->projectHelper = $projectHelper;
        $this->hmacSignatureHelper = $hmacSignatureHelper;
    }

    /**
     * @Route("/verify", name="verification_api_verify")
     */
    public function verify(Request $request, SubmitTokenRepository $submitTokenRepository)
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

                $this->getDoctrine()->getManager()->flush();

                return new JsonResponse(['error' => true, 'errorMessage' => 'Validation failed.']);
            }
        }

        $validationSignature = $this->hmacSignatureHelper->createSignature($submission->getValidationToken(), $activeProject->getPrivateKey());
        if ($request->request->get('validationSignature') !== $validationSignature || $request->request->get('formSignature') !== $submission->getSignature()) {
            $submission->setValid(false);
            $this->getDoctrine()->getManager()->flush();

            return new JsonResponse(['error' => true, 'errorMessage' => 'Validation failed.']);
        }

        $verificationSignature = $this->hmacSignatureHelper->createSignature($validationSignature . $submission->getSignature(), $activeProject->getPrivateKey());

        $submission->setValid(true);
        $this->getDoctrine()->getManager()->flush();

        return new JsonResponse(['valid' => true, 'verificationSignature' => $verificationSignature]);
    }
}