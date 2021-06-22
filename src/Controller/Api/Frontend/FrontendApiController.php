<?php

namespace Mosparo\Controller\Api\Frontend;

use DateTime;
use Mosparo\Entity\Project;
use Mosparo\Entity\Submission;
use Mosparo\Entity\SubmitToken;
use Mosparo\Helper\ActiveProjectHelper;
use Mosparo\Helper\RuleHelper;
use Mosparo\Util\TokenGenerator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/api/frontend")
 */
class FrontendApiController extends AbstractController
{
    protected $activeProjectHelper;

    protected $tokenGenerator;

    public function __construct(ActiveProjectHelper $activeProjectHelper, TokenGenerator $tokenGenerator, RuleHelper $ruleHelper)
    {
        $this->activeProjectHelper = $activeProjectHelper;
        $this->tokenGenerator = $tokenGenerator;
        $this->ruleHelper = $ruleHelper;
    }

    /**
     * @Route("/request-submit-token", name="frontend_api_request_submit_token")
     */
    public function request(Request $request)
    {
        // If there is no active project, we cannot do anything.
        if (!$this->activeProjectHelper->hasActiveProject()) {
            return new JsonResponse(['error' => true, 'errorMessage' => 'No project available.', 'activeProject' => $this->activeProjectHelper->getActiveProject()]);
        }

        $submitToken = new SubmitToken();
        $submitToken->setIpAddress($request->getClientIp());
        $submitToken->setCreatedAt(new DateTime());
        $submitToken->setToken($this->tokenGenerator->generateToken());

        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->persist($submitToken);
        $entityManager->flush();

        return new JsonResponse(['submitToken' => $submitToken->getToken()]);
    }

    /**
     * @Route("/check-form-data", name="frontend_api_check_form_data")
     */
    public function checkFormData(Request $request)
    {
        $entityManager = $this->getDoctrine()->getManager();

        // If there is no active project, we cannot do anything.
        if (!$this->activeProjectHelper->hasActiveProject()) {
            return new JsonResponse(['error' => true, 'errorMessage' => 'No project available.']);
        }

        if (!$request->request->has('_mosparo_submitToken')) {
            return new JsonResponse(['error' => true, 'errorMessage' => 'Submit token not set.']);
        }

        $submitTokenRepository = $entityManager->getRepository(SubmitToken::class);
        $submitToken = $submitTokenRepository->findOneBy([
            'token' => $request->request->get('_mosparo_submitToken'),

        ]);

        if (!$submitToken->isValid()) {
            return new JsonResponse(['error' => true, 'errorMessage' => 'Submit token not valid.']);
        }

        if (!$request->request->has('formData')) {
            return new JsonResponse(['error' => true, 'errorMessage' => 'Form data not set.']);
        }

        $formData = json_decode($request->request->get('formData'), true);
        if ($formData === null) {
            return new JsonResponse(['error' => true, 'errorMessage' => 'Form data not valid.']);
        }

        $submission = new Submission();
        $submission->setSubmitToken($submitToken);
        $submission->setSubmittedAt(new DateTime());
        $submission->setData([
            'formData' => $formData,
            'client' => [
                [
                    'name' => 'ipAddress',
                    'value' => $request->getClientIp(),
                    'fieldPath' => 'ipAddress'
                ]
            ]
        ]);

        $this->ruleHelper->checkRequest($submission);

        $entityManager->persist($submission);
        $entityManager->flush();

        return new JsonResponse(['valid' => (!$submission->isSpam()), 'validationToken' => $submission->getValidationToken()]);
    }
}