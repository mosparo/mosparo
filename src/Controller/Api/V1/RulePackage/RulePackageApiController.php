<?php

namespace Mosparo\Controller\Api\V1\RulePackage;

use Doctrine\ORM\EntityManagerInterface;
use Mosparo\Enum\RulePackageType;
use Mosparo\Helper\ProjectHelper;
use Mosparo\Helper\RulePackageHelper;
use Mosparo\Repository\RulePackageRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/v1/rule-package')]
class RulePackageApiController extends AbstractController
{
    protected ProjectHelper $projectHelper;

    protected RulePackageHelper $rulePackageHelper;

    public function __construct(ProjectHelper $projectHelper, RulePackageHelper $rulePackageHelper)
    {
        $this->projectHelper = $projectHelper;
        $this->rulePackageHelper = $rulePackageHelper;
    }

    #[Route('/import', name: 'rule_package_api_import')]
    public function import(Request $request, EntityManagerInterface $entityManager, RulePackageRepository $rulePackageRepository): Response
    {
        // If there is no active project, we cannot do anything.
        if (!$this->projectHelper->hasActiveProject()) {
            return new JsonResponse(['error' => true, 'errorMessage' => 'No project available.']);
        }

        $activeProject = $this->projectHelper->getActiveProject();

        if (!$request->request->has('rulePackageId') || !$request->request->has('rulePackageContent')) {
            // Prepare the API debug data
            $debugInformation = [];
            if ($activeProject->isApiDebugMode()) {
                $debugInformation['debugInformation'] = [
                    'reason' => 'required_parameter_missing',
                    'hasRulePackageId' => $request->request->has('rulePackageId'),
                    'hasRulePackageContent' => $request->request->has('rulePackageContent'),
                ];
            }

            return new JsonResponse(['error' => true, 'errorMessage' => 'Required parameter missing.'] + $debugInformation);
        }

        $rulePackage = $rulePackageRepository->find($request->request->get('rulePackageId'));
        if ($rulePackage === null) {
            return new JsonResponse(['error' => true, 'errorMessage' => 'Rule package not found.']);
        }

        if ($rulePackage->getType() !== RulePackageType::MANUALLY_VIA_API) {
            // Prepare the API debug data
            $debugInformation = [];
            if ($activeProject->isApiDebugMode()) {
                $debugInformation['debugInformation'] = [
                    'reason' => 'rule_package_type_invalid',
                    'expectedType' => RulePackageType::MANUALLY_VIA_API->name,
                    'receivedType' => $rulePackage->geTType()->name
                ];
            }

            return new JsonResponse([
                'error' => true,
                'errorMessage' => sprintf('Rule package type (%s) is not allowed.', $rulePackage->getType()->name)
            ] + $debugInformation);
        }

        $verifiedHash = false;
        $rulePackageContent = $request->request->get('rulePackageContent');

        if ($request->request->has('rulePackageHash') && trim($request->request->get('rulePackageHash'))) {
            if (hash('sha256', $rulePackageContent) !== $request->request->get('rulePackageHash')) {
                // Prepare the API debug data
                $debugInformation = [];
                if ($activeProject->isApiDebugMode()) {
                    $debugInformation['debugInformation'] = [
                        'reason' => 'rule_package_content_hash_invalid',
                        'sentHash' => $request->request->get('rulePackageHash'),
                        'generatedHash' => hash('sha256', $rulePackageContent),
                    ];
                }

                return new JsonResponse(['error' => true, 'errorMessage' => 'The specified hash is invalid for the given content.'] + $debugInformation);
            }

            $verifiedHash = true;
        }

        if (!trim($rulePackageContent)) {
            return new JsonResponse(['error' => true, 'errorMessage' => 'Rule package content is empty.']);
        }

        // Validate and process the content
        try {
            $this->rulePackageHelper->validateAndProcessContent($rulePackage, $rulePackageContent, false);
        } catch (\Exception $e) {
            // Prepare the API debug data
            $debugInformation = [];
            if ($activeProject->isApiDebugMode()) {
                $debugInformation['debugInformation'] = [
                    'reason' => 'general_error',
                    'exceptionMessage' => $e->getMessage(),
                ];
            }

            return new JsonResponse(['error' => true, 'errorMessage' => 'A general error occurred.'] + $debugInformation);
        }

        // Store the rule package cache
        $entityManager->flush();

        return new JsonResponse([
            'successful' => true,
            'verifiedHash' => $verifiedHash,
        ]);
    }
}