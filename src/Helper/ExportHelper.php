<?php

namespace Mosparo\Helper;

use Doctrine\ORM\EntityManagerInterface;
use Mosparo\Entity\Project;
use Mosparo\Entity\Rule;
use Mosparo\Entity\Ruleset;
use Mosparo\Entity\SecurityGuideline;
use Mosparo\Exception\ExportException;
use Mosparo\Specifications\Specifications;
use Symfony\Component\String\Slugger\SluggerInterface;

class ExportHelper
{
    protected EntityManagerInterface $entityManager;

    protected ProjectHelper $projectHelper;

    protected SluggerInterface $slugger;

    public function __construct(EntityManagerInterface $entityManager, ProjectHelper $projectHelper, SluggerInterface $slugger)
    {
        $this->entityManager = $entityManager;
        $this->projectHelper = $projectHelper;
        $this->slugger = $slugger;
    }

    public function generateFileName(Project $project): string
    {
        $projectName = $this->slugger->slug($project->getName());
        $fileName = 'export-' . $projectName . '-' . (new \DateTime())->format('Y-m-d_H-i-s') . '.json';

        return $fileName;
    }

    public function exportProject(Project $project, bool $exportGeneralSettings, bool $exportDesignSettings, bool $exportSecuritySettings, bool $exportRules, bool $exportRulesets): array
    {
        if (!$exportGeneralSettings && !$exportDesignSettings && !$exportSecuritySettings && !$exportRules && !$exportRulesets) {
            throw new ExportException('Select at least one element that you want to export.', ExportException::EMPTY_REQUEST);
        }

        // Change the active project for the database queries
        $activeProject = $this->projectHelper->getActiveProject();
        $this->projectHelper->setActiveProject($project);

        $data = [];

        if ($exportGeneralSettings) {
            $data['name'] = $project->getName();
            $data['description'] = $project->getDescription();
            $data['hosts'] = array_values($project->getHosts());
            $data['status'] = $project->getStatus();
            $data['spamScore'] = $project->getSpamScore();
            $data['statisticStorageLimit'] = $project->getStatisticStorageLimit();
            $data['apiDebugMode'] = $project->isApiDebugMode();
            $data['verificationSimulationMode'] = $project->isVerificationSimulationMode();
        }

        if ($exportDesignSettings) {
            $data['design'] = $this->exportDesignSettings($project);
        }

        if ($exportSecuritySettings) {
            $data['security'] = $this->exportSecuritySettings($project);
            $data['securityGuidelines'] = $this->exportSecurityGuidelines();
        }

        if ($exportRules) {
            $data['rules'] = $this->exportRules();
        }

        if ($exportRulesets) {
            $data['rulesets'] = $this->exportRulesets();
        }

        // Change the active project back
        $this->projectHelper->setActiveProject($activeProject);

        return [
            'specificationsVersion' => Specifications::SPECIFICATIONS_VERSION,
            'exportedAt' => (new \DateTime())->format(\DateTimeImmutable::RFC3339),
            'project' => $data,
        ];
    }

    protected function exportDesignSettings(Project $project): array
    {
        return $this->exportProjectSettings($project, [
            'designMode',
            'boxSize',
            'positionContainer',
            'displayContent',

            // Visible: Simple
            'colorWebsiteBackground',
            'colorWebsiteForeground',
            'colorWebsiteAccent',
            'colorHover',
            'colorSuccess',
            'colorFailure',

            // Visible: Advanced
            'boxRadius',
            'boxBorderWidth',
            'colorBackground',
            'colorBorder',
            'colorCheckbox',
            'colorText',
            'colorShadow',
            'colorShadowInset',
            'colorFocusCheckbox',
            'colorFocusCheckboxShadow',
            'colorLoadingCheckbox',
            'colorLoadingCheckboxAnimatedCircle',
            'colorSuccessBackground',
            'colorSuccessBorder',
            'colorSuccessCheckbox',
            'colorSuccessText',
            'colorSuccessShadow',
            'colorSuccessShadowInset',
            'colorFailureBackground',
            'colorFailureBorder',
            'colorFailureCheckbox',
            'colorFailureText',
            'colorFailureTextError',
            'colorFailureShadow',
            'colorFailureShadowInset',
            'showPingAnimation',
            'showMosparoLogo',

            // Invisible: Simple
            'fullPageOverlay',
            'colorLoaderBackground',
            'colorLoaderText',
            'colorLoaderCircle',
        ]);
    }

    protected function exportSecuritySettings(Project $project): array
    {
        return $this->exportProjectSettings($project, [
            'minimumTimeActive',
            'minimumTimeSeconds',

            'honeypotFieldActive',
            'honeypotFieldName',

            'delayActive',
            'delayNumberOfRequests',
            'delayDetectionTimeFrame',
            'delayTime',
            'delayMultiplicator',

            'lockoutActive',
            'lockoutNumberOfRequests',
            'lockoutDetectionTimeFrame',
            'lockoutTime',
            'lockoutMultiplicator',

            'equalSubmissionsActive',
            'equalSubmissionsNumberOfEqualSubmissions',
            'equalSubmissionsTimeFrame',
            'equalSubmissionsBasedOnIpAddress',

            'ipAllowList',
        ]);
    }

    protected function exportProjectSettings(Project $project, array $settingKeys): array
    {
        $settings = [];
        foreach ($project->getConfigValues() as $key => $value) {
            if (in_array($key, $settingKeys)) {
                $settings[] = [
                    'name' => $key,
                    'value' => $value,
                ];
            }
        }

        return $settings;
    }

    protected function exportSecurityGuidelines(): array
    {
        $securityGuidelines = [];
        $securityGuidelinesRepository = $this->entityManager->getRepository(SecurityGuideline::class);

        foreach ($securityGuidelinesRepository->findAll() as $securityGuideline) {
            $securityGuidelines[] = $securityGuideline->toArray();
        }

        return $securityGuidelines;
    }

    protected function exportRules(): array
    {
        $rules = [];
        $ruleRepository = $this->entityManager->getRepository(Rule::class);

        foreach ($ruleRepository->findAll() as $rule) {
            $items = [];
            foreach ($rule->getItems() as $item) {
                $items[] = [
                    'uuid' => $item->getUuid(),
                    'type' => $item->getType(),
                    'value' => $item->getValue(),
                    'rating' => $item->getSpamRatingFactor() ?? 1,
                ];
            }

            $rules[] = [
                'name' => $rule->getName(),
                'description' => $rule->getDescription(),
                'type' => $rule->getType(),
                'status' => (bool) $rule->getStatus(),
                'spamRatingFactor' => $rule->getSpamRatingFactor() ?? 1,
                'items' => $items,
                'uuid' => $rule->getUuid(),
            ];
        }

        return $rules;
    }

    protected function exportRulesets(): array
    {
        $rulesets = [];
        $rulesetRepository = $this->entityManager->getRepository(Ruleset::class);

        foreach ($rulesetRepository->findAll() as $ruleset) {
            $rulesets[] = [
                'name' => $ruleset->getName(),
                'url' => $ruleset->getUrl(),
                'status' => (bool) $ruleset->getStatus(),
                'spamRatingFactor' => $ruleset->getSpamRatingFactor() ?? 1,
            ];
        }

        return $rulesets;
    }
}