<?php

namespace Mosparo\Helper;

use Doctrine\ORM\EntityManagerInterface;
use Mosparo\Entity\Project;
use Mosparo\Entity\Rule;
use Mosparo\Entity\RuleItem;
use Mosparo\Entity\RulePackage;
use Mosparo\Entity\SecurityGuideline;
use Mosparo\Enum\RulePackageType;
use Mosparo\Exception\ImportException;
use Mosparo\Specifications\Specifications;
use Opis\JsonSchema\Validator;

class ImportHelper
{
    protected EntityManagerInterface $entityManager;

    protected ProjectHelper $projectHelper;

    protected DesignHelper $designHelper;

    protected RulePackageHelper $rulePackageHelper;

    protected string $importDirectory;

    public function __construct(
        EntityManagerInterface $entityManager,
        ProjectHelper $projectHelper,
        DesignHelper $designHelper,
        RulePackageHelper $rulePackageHelper,
        string $importDirectory
    ) {
        $this->entityManager = $entityManager;
        $this->projectHelper = $projectHelper;
        $this->designHelper = $designHelper;
        $this->rulePackageHelper = $rulePackageHelper;
        $this->importDirectory = $importDirectory;
    }

    public function getImportFilePathAndName(Project $project)
    {
        return [$this->importDirectory, $project->getId() . '_import_' . uniqid() . '.json'];
    }

    public function storeJobData(array $importData, $token = ''): string
    {
        if (!$token) {
            $token = uniqid();
        }

        file_put_contents($this->importDirectory . '/job_data_' . $token . '.json', json_encode($importData));

        return $token;
    }

    public function loadJobData(string $token): array
    {
        $filePath = $this->importDirectory . '/job_data_' . $token . '.json';
        if (!file_exists($filePath)) {
            throw new ImportException(sprintf('Job data file for token %s does not exist.', $token), ImportException::JOB_DATA_FILE_NOT_FOUND);
        }

        $rawData = file_get_contents($filePath);
        $data = json_decode($rawData, true);

        if (!$data) {
            throw new ImportException(sprintf('Job data for token %s are invalid.', $token), ImportException::JOB_DATA_INVALID);
        }

        return $data;
    }

    public function simulateImport(?string $token, array $jobData = []): array
    {
        if ($token !== null) {
            $jobData = $this->loadJobData($token);
        }

        // Get the project to make sure we're using the right project
        $projectRepository = $this->entityManager->getRepository(Project::class);
        $project = $projectRepository->find($jobData['projectId'] ?? 0);

        if (!$project) {
            throw new ImportException(sprintf('Cannot find project by id %d.', $jobData['projectId'] ?? 0), ImportException::PROJECT_NOT_AVAILABLE);
        }

        $importData = $this->loadImportData($jobData);
        if (version_compare($importData['specificationsVersion'], Specifications::SPECIFICATIONS_VERSION, '>')) {
            throw new ImportException(
                sprintf(
                    'Import file was created with specifications version %s, but this mosparo uses %s.',
                    $importData['specificationsVersion'],
                    Specifications::SPECIFICATIONS_VERSION
                ),
                ImportException::WRONG_SPECIFICATIONS_VERSION
            );
        }

        $activeProject = $this->projectHelper->getActiveProject();
        $this->projectHelper->setActiveProject($project);

        // Detect the changes
        $changes = $this->findChanges($project, $jobData, $importData);

        // Set the originally active project
        if ($activeProject) {
            $this->projectHelper->setActiveProject($activeProject);
        }

        return [$jobData, $importData, $this->hasChanges($changes), $changes];
    }

    public function executeImport(?string $token, array $jobData = [])
    {
        if ($token !== null) {
            $jobData = $this->loadJobData($token);
        }

        // Get the project to make sure we're using the right project
        $projectRepository = $this->entityManager->getRepository(Project::class);
        $project = $projectRepository->find($jobData['projectId'] ?? 0);

        if (!$project) {
            throw new ImportException(sprintf('Cannot find project by id %d.', $jobData['projectId'] ?? 0), ImportException::PROJECT_NOT_AVAILABLE);
        }

        $activeProject = $this->projectHelper->getActiveProject();
        $this->projectHelper->setActiveProject($project);

        // Load the changes from the job data
        $changes = $jobData['changes'] ?? null;
        if (!$changes) {
            throw new ImportException('No changes available.', ImportException::NO_CHANGES_AVAILABLE);
        }

        // Execute the changes
        $refreshCssCache = false;
        $modifiedRulePackages = false;
        foreach ($changes as $sectionKey => $sectionChanges) {
            if (in_array($sectionKey, ['generalSettings', 'designSettings', 'securitySettings'])) {
                $this->executeProjectChanges($project, $sectionChanges);

                if ($sectionKey === 'designSettings' && $sectionChanges) {
                    $refreshCssCache = true;
                }
            } else if ($sectionKey === 'securityGuidelines') {
                $this->executeSecurityGuidelineChanges($sectionChanges);
            } else if ($sectionKey === 'rules') {
                $this->executeRuleChanges($sectionChanges);
            } else if ($sectionKey === 'rulePackages') {
                $modifiedRulePackages = $this->executeRulePackageChanges($sectionChanges);
            }

            $this->entityManager->flush();

            // Update the modified rule packages.
            if ($modifiedRulePackages) {
                try {
                    $this->rulePackageHelper->fetchRulePackages($modifiedRulePackages);
                } catch (\Exception $e) {
                    // Ignore all errors because the method call above is a helper but not required.
                }
            }
        }

        // Prepare the css cache
        if ($refreshCssCache) {
            $this->designHelper->generateCssCache($project);
        }

        // Remove the two files since everything is done
        if ($token !== null) {
            $this->removeFiles($token, $jobData['importDataFilename']);
        }

        // Set the originally active project
        $this->projectHelper->setActiveProject($activeProject);
    }

    protected function loadImportData(array $jobData): array
    {
        $filePath = $jobData['importDataFilename'];
        if (!str_starts_with($filePath, '/')) {
            $filePath = $this->importDirectory . '/' . $jobData['importDataFilename'];
        }

        if (!file_exists($filePath)) {
            throw new ImportException(sprintf('Import file "%s" does not exist.', $jobData['importDataFilename']), ImportException::IMPORT_FILE_NOT_FOUND);
        }

        $importData = file_get_contents($filePath);
        $jsonData = json_decode($importData);

        $validator = new Validator();
        $validator->resolver()->registerPrefix('http://schema.mosparo.io/', Specifications::getJsonSchemaPath(''));

        $result = $validator->validate($jsonData, 'http://schema.mosparo.io/project.json');

        if (!$result->isValid()) {
            throw new ImportException(sprintf('Import file "%s" is not valid.', $jobData['importDataFilename']), ImportException::IMPORT_FILE_INVALID);
        }

        return json_decode($importData, true);
    }

    protected function removeFiles($token, $importFileName)
    {
        $jobDataFilePath = $this->importDirectory . '/job_data_' . $token . '.json';
        if (file_exists($jobDataFilePath)) {
            unlink($jobDataFilePath);
        }

        $importDataFilePath = $this->importDirectory . '/' . $importFileName;
        if (file_exists($importDataFilePath)) {
            unlink($importDataFilePath);
        }
    }

    protected function hasChanges(array $changes): bool
    {
        return (
            !empty($changes['generalSettings'] ?? []) ||
            !empty($changes['designSettings'] ?? []) ||
            !empty($changes['securitySettings'] ?? []) ||
            !empty($changes['securityGuidelines'] ?? []) ||
            !empty($changes['rules'] ?? []) ||
            !empty($changes['rulePackages'] ?? [])
        );
    }

    protected function findChanges(Project $project, array $jobData, array $importData): array
    {
        $changes = [];

        if ($jobData['importGeneralSettings']) {
            $changes['generalSettings'] = $this->findGeneralSettingsChanges($project, $importData['project']);
        }

        if ($jobData['importDesignSettings'] && isset($importData['project']['design'])) {
            $changes['designSettings'] = $this->findSettingChanges($project, $importData['project']['design']);
        }

        if ($jobData['importSecuritySettings'] && isset($importData['project']['security'])) {
            $changes['securitySettings'] = $this->findSettingChanges($project, $importData['project']['security']);

            if (isset($importData['project']['securityGuidelines'])) {
                $changes['securityGuidelines'] = $this->findSecurityGuidelineChanges($importData['project']['securityGuidelines']);
            }
        }

        if ($jobData['importRules'] && isset($importData['project']['rules'])) {
            $changes['rules'] = $this->findRuleChanges($importData['project']['rules'], $jobData['handlingExistingRules']);
        }

        if ($jobData['importRulePackages'] && isset($importData['project']['rulePackages'])) {
            $changes['rulePackages'] = $this->findRulePackageChanges($importData['project']['rulePackages']);
        }

        return $changes;
    }

    protected function findGeneralSettingsChanges(Project $project, array $importData): array
    {
        $changes = [];

        if (isset($importData['name']) && $project->getName() !== $importData['name']) {
            $changes[] = [
                'key' => 'name',
                'oldValue' => $project->getName(),
                'newValue' => $importData['name']
            ];
        }

        if (isset($importData['description']) && $project->getDescription() !== $importData['description']) {
            $changes[] = [
                'key' => 'description',
                'oldValue' => $project->getDescription(),
                'newValue' => $importData['description']
            ];
        }

        if (
            isset($importData['hosts']) &&
            (
                array_diff($importData['hosts'], array_values($project->getHosts())) ||
                array_diff(array_values($project->getHosts()), $importData['hosts'])
            )
        ) {
            $changes[] = [
                'key' => 'hosts',
                'oldValue' => array_values($project->getHosts()),
                'newValue' => $importData['hosts']
            ];
        }

        if (isset($importData['status']) && $project->getStatus() !== $importData['status']) {
            $changes[] = [
                'key' => 'status',
                'oldValue' => $project->getStatus(),
                'newValue' => $importData['status']
            ];
        }

        if (isset($importData['spamScore']) && $project->getSpamScore() !== $importData['spamScore']) {
            $changes[] = [
                'key' => 'spamScore',
                'oldValue' => $project->getSpamScore(),
                'newValue' => $importData['spamScore']
            ];
        }

        if (isset($importData['statisticStorageLimit']) && $project->getStatisticStorageLimit() !== $importData['statisticStorageLimit']) {
            $changes[] = [
                'key' => 'statisticStorageLimit',
                'oldValue' => $project->getStatisticStorageLimit(),
                'newValue' => $importData['statisticStorageLimit']
            ];
        }

        if (isset($importData['apiDebugMode']) && $project->isApiDebugMode() !== $importData['apiDebugMode']) {
            $changes[] = [
                'key' => 'apiDebugMode',
                'oldValue' => $project->isApiDebugMode(),
                'newValue' => $importData['apiDebugMode']
            ];
        }

        if (isset($importData['verificationSimulationMode']) && $project->isVerificationSimulationMode() !== $importData['verificationSimulationMode']) {
            $changes[] = [
                'key' => 'verificationSimulationMode',
                'oldValue' => $project->isVerificationSimulationMode(),
                'newValue' => $importData['verificationSimulationMode']
            ];
        }

        return $changes;
    }

    protected function findSettingChanges(Project $project, array $settings): array
    {
        $changes = [];

        foreach ($settings as $setting) {
            $oldValue = $project->getConfigValue($setting['name']);
            $newValue = $setting['value'];

            if ($oldValue !== $newValue) {
                $changes[] = [
                    'key' => $setting['name'],
                    'oldValue' => $oldValue,
                    'newValue' => $newValue
                ];
            }
        }

        return $changes;
    }

    protected function findSecurityGuidelineChanges(array $securityGuidelines): array
    {
        $changes = [];
        $securityGuidelineRepository = $this->entityManager->getRepository(SecurityGuideline::class);

        foreach ($securityGuidelines as $guideline) {
            $storedGuideline = $securityGuidelineRepository->findOneBy(['uuid' => $guideline['uuid']]);

            $mode = 'add';
            $storedGuidelineId = null;
            $storedGuidelineName = null;
            $changedCriteria = true;
            $changedSettings = true;
            if ($storedGuideline !== null) {
                if ($storedGuideline->isEqual($guideline)) {
                    // Everything up to date, no change required.
                    continue;
                }

                $mode = 'modify';
                $storedGuidelineId = $storedGuideline->getId();
                $storedGuidelineName = $storedGuideline->getName();

                if ($storedGuideline->areCriteriaEqual($guideline)) {
                    $changedCriteria = false;
                }

                if ($storedGuideline->areSettingsEqual($guideline)) {
                    $changedSettings = false;
                }
            }

            $changes[] = [
                'mode' => $mode,
                'storedGuideline' => [
                    'id' => $storedGuidelineId,
                    'name' => $storedGuidelineName
                ],
                'importedGuideline' => $guideline,
                'changedCriteria' => $changedCriteria,
                'changedSettings' => $changedSettings,
            ];
        }

        return $changes;
    }

    protected function findRuleChanges(array $rules, string $handlingExistingRules): array
    {
        $changes = [];
        $ruleRepository = $this->entityManager->getRepository(Rule::class);

        foreach ($rules as $rule) {
            $storedRule = $ruleRepository->findOneBy([
                'uuid' => $rule['uuid'],
                'type' => $rule['type'],
            ]);

            $storedRuleId = null;
            $storedRuleName = null;
            $itemChanges = [
                'add' => [],
                'modify' => [],
                'remove' => [],
            ];
            if ($storedRule === null || $handlingExistingRules === 'add') {
                $mode = 'add';
                $itemChanges['add'] = $rule['items'];
            } else if ($storedRule !== null) {
                $mode = 'modify';
                $storedRuleId = $storedRule->getId();
                $storedRuleName = $storedRule->getName();

                $items = $storedRule->getItems()->toArray();

                foreach ($rule['items'] as $item) {
                    $foundItems = array_filter($items, function (RuleItem $ruleItem) use ($item) {
                        if ($ruleItem->getUuid() === $item['uuid']) {
                            return true;
                        }

                        if ($ruleItem->getValue() === $item['value']) {
                            return true;
                        }

                        return false;
                    });

                    if ($foundItems) {
                        $storedItemKey = key($foundItems);
                        $storedItem = current($foundItems);

                        if (
                            $storedItem->getType() !== $item['type'] ||
                            $storedItem->getValue() !== $item['value'] ||
                            !$this->isSpamRatingFactorEqual($storedItem->getSpamRatingFactor(), (float) $item['rating'])
                        ) {
                            $itemChanges['modify'][] = array_merge(
                                [
                                    'storedItemId' => $storedItem->getId(),
                                    'storedRuleId' => $storedRule->getId(),
                                ],
                                $item
                            );
                        }

                        unset($items[$storedItemKey]);
                    } else {
                        $itemChanges['add'][] = $item;
                    }
                }

                // OVERRIDE only: Remove the items which were not handled by the foreach above
                if ($handlingExistingRules === 'override') {
                    foreach ($items as $item) {
                        $itemChanges['remove'][] = [
                            'storedItemId' => $item->getId(),
                            'storedRuleId' => $item->getId(),
                        ];
                    }
                }

                if (
                    $storedRule->getName() === $rule['name'] &&
                    $storedRule->getDescription() === $rule['description'] &&
                    $this->isSpamRatingFactorEqual($storedRule->getSpamRatingFactor(), (float) $rule['spamRatingFactor']) &&
                    $storedRule->getStatus() === (int) $rule['status'] &&
                    count($itemChanges['add']) === 0 &&
                    count($itemChanges['modify']) === 0 &&
                    count($itemChanges['remove']) === 0
                ) {
                    // Everything up to date, no change required.
                    continue;
                }
            }

            $changes[] = [
                'mode' => $mode,
                'submode' => $handlingExistingRules,
                'itemChanges' => $itemChanges,
                'storedRule' => [
                    'id' => $storedRuleId,
                    'name' => $storedRuleName,
                ],
                'importedRule' => $rule,
            ];
        }

        return $changes;
    }

    protected function isSpamRatingFactorEqual(?float $storedRating, ?float $importedRating)
    {
        if ($storedRating == 1) {
            $storedRating = null;
        }

        if ($importedRating == 1) {
            $importedRating = null;
        }

        return ($storedRating === $importedRating);
    }

    protected function findRulePackageChanges(array $rulePackages): array
    {
        $changes = [];
        $rulePackageRepository = $this->entityManager->getRepository(RulePackage::class);

        foreach ($rulePackages as $rulePackage) {
            $storedRulePackage = $rulePackageRepository->findOneBy([
                'uuid' => $rulePackage['uuid'],
                'type' => $rulePackage['type'],
            ]);

            $mode = 'add';
            $storedRulePackageId = null;
            $storedRulePackageName = null;
            if ($storedRulePackage !== null) {
                $storedSpamRatingFactor = $storedRulePackage->getSpamRatingFactor() ?: 1.0;

                if (
                    $storedRulePackage->getName() === $rulePackage['name'] &&
                    $storedSpamRatingFactor === (float) $rulePackage['spamRatingFactor'] &&
                    $storedRulePackage->getStatus() === (bool) $rulePackage['status'] &&
                    (string) $storedRulePackage->getSource() === (string) $rulePackage['source']
                ) {
                    // Everything up to date, no change required.
                    continue;
                }

                $mode = 'modify';
                $storedRulePackageId = $storedRulePackage->getId();
                $storedRulePackageName = $storedRulePackage->getName();
            }

            $changes[] = [
                'mode' => $mode,
                'storedRulePackage' => [
                    'id' => $storedRulePackageId,
                    'name' => $storedRulePackageName
                ],
                'importedRulePackage' => $rulePackage,
            ];
        }

        return $changes;
    }

    protected function executeProjectChanges(Project $project, array $sectionChanges)
    {
        foreach ($sectionChanges as $change) {
            $key = $change['key'];
            if (in_array($key, ['name', 'description', 'hosts', 'status', 'spamScore', 'statisticStorageLimit', 'apiDebugMode', 'verificationSimulationMode'])) {
                switch ($key) {
                    case 'name':
                        $project->setName($change['newValue']);
                    break;
                    case 'description':
                        $project->setDescription($change['newValue']);
                    break;
                    case 'hosts':
                        $project->setHosts($change['newValue']);
                    break;
                    case 'status':
                        $project->setStatus($change['newValue']);
                    break;
                    case 'spamScore':
                        $project->setSpamScore($change['newValue']);
                    break;
                    case 'statisticStorageLimit':
                        $project->setStatisticStorageLimit($change['newValue']);
                    break;
                    case 'apiDebugMode':
                        $project->setApiDebugMode($change['newValue']);
                    break;
                    case 'verificationSimulationMode':
                        $project->setVerificationSimulationMode($change['newValue']);
                    break;
                }
            } else {
                $project->setConfigValue($key, $change['newValue']);
            }
        }
    }

    protected function executeSecurityGuidelineChanges(array $sectionChanges)
    {
        $securityGuidelineRepository = $this->entityManager->getRepository(SecurityGuideline::class);

        foreach ($sectionChanges as $change) {
            $importedSecurityGuideline = $change['importedGuideline'];

            if ($change['mode'] === 'add') {
                $securityGuideline = new SecurityGuideline();
                $securityGuideline->setUuid($importedSecurityGuideline['uuid']);

                $this->entityManager->persist($securityGuideline);
            } else if ($change['mode'] === 'modify') {
                $securityGuideline = $securityGuidelineRepository->find($change['storedGuideline']['id']);
                if (!$securityGuideline) {
                    throw new ImportException('Stored security guideline not found.', ImportException::STORED_SECURITY_GUIDELINE_NOT_FOUND);
                }
            }

            $securityGuideline->setName($importedSecurityGuideline['name']);
            $securityGuideline->setDescription($importedSecurityGuideline['description']);
            $securityGuideline->setPriority($importedSecurityGuideline['priority']);
            $securityGuideline->setSubnets($importedSecurityGuideline['subnets']);
            $securityGuideline->setCountryCodes($importedSecurityGuideline['countryCodes']);
            $securityGuideline->setAsNumbers($importedSecurityGuideline['asNumbers']);

            foreach ($importedSecurityGuideline['securitySettings'] as $setting) {
                $securityGuideline->setConfigValue($setting['name'], $setting['value']);
            }
        }
    }

    protected function executeRuleChanges(array $sectionChanges)
    {
        $ruleRepository = $this->entityManager->getRepository(Rule::class);

        foreach ($sectionChanges as $change) {
            $importedRule = $change['importedRule'];

            if ($change['mode'] === 'add') {
                $rule = new Rule();
                $rule->setUuid($importedRule['uuid']);
                $rule->setType($importedRule['type']);

                $this->entityManager->persist($rule);
            } else if ($change['mode'] === 'modify') {
                $rule = $ruleRepository->find($change['storedRule']['id']);
                if (!$rule) {
                    throw new ImportException('Stored rule not found.', ImportException::STORED_RULE_NOT_FOUND);
                }
            }

            $rule->setName($importedRule['name']);
            $rule->setDescription($importedRule['description']);
            $rule->setStatus((int) $importedRule['status']);
            $rule->setSpamRatingFactor((float) $importedRule['spamRatingFactor']);

            // Add the new items
            foreach ($change['itemChanges']['add'] as $itemChange) {
                $ruleItem = new RuleItem();
                $ruleItem->setRule($rule);
                $ruleItem->setUuid($itemChange['uuid']);
                $ruleItem->setType($itemChange['type']);
                $ruleItem->setValue($itemChange['value']);
                $ruleItem->setSpamRatingFactor((float) $itemChange['rating']);

                $this->entityManager->persist($ruleItem);
            }

            // Modify the stored items
            foreach ($change['itemChanges']['modify'] as $itemChange) {
                $storedItemId = $itemChange['storedItemId'];
                $ruleItem = $rule->getItems()->findFirst(function ($idx, RuleItem $item) use ($storedItemId) {
                    if ($item->getId() === $storedItemId) {
                        return true;
                    }
                    return false;
                });
                if (!$ruleItem) {
                    throw new ImportException('Stored rule item not found.', ImportException::STORED_RULE_ITEM_NOT_FOUND);
                }

                $ruleItem->setType($itemChange['type']);
                $ruleItem->setValue($itemChange['value']);
                $ruleItem->setSpamRatingFactor($itemChange['rating']);
            }

            // Remove the stored items
            foreach ($change['itemChanges']['remove'] as $itemChange) {
                $storedItemId = $itemChange['storedItemId'];
                $ruleItem = $rule->getItems()->findFirst(function ($idx, RuleItem $item) use ($storedItemId) {
                    if ($item->getId() === $storedItemId) {
                        return true;
                    }
                    return false;
                });
                if (!$ruleItem) {
                    throw new ImportException('Stored rule item not found.', ImportException::STORED_RULE_ITEM_NOT_FOUND);
                }

                $rule->removeItem($ruleItem);
                $this->entityManager->remove($ruleItem);
            }
        }
    }

    protected function executeRulePackageChanges(array $sectionChanges): array
    {
        $modifiedRulePackages = [];
        $rulePackageRepository = $this->entityManager->getRepository(RulePackage::class);

        foreach ($sectionChanges as $change) {
            $importedRulePackage = $change['importedRulePackage'];

            if ($change['mode'] === 'add') {
                $rulePackage = new RulePackage();
                $rulePackage->setUuid($importedRulePackage['uuid']);
                $rulePackage->setType(RulePackageType::from($importedRulePackage['type']));

                $this->entityManager->persist($rulePackage);
            } else if ($change['mode'] === 'modify') {
                $rulePackage = $rulePackageRepository->find($change['storedRulePackage']['id']);
                if (!$rulePackage) {
                    throw new ImportException('Stored rule package not found.', ImportException::STORED_RULE_PACKAGE_NOT_FOUND);
                }
            }

            $rulePackage->setName($importedRulePackage['name']);
            $rulePackage->setSource($importedRulePackage['source']);
            $rulePackage->setStatus((bool) $importedRulePackage['status']);
            $rulePackage->setSpamRatingFactor($importedRulePackage['spamRatingFactor']);

            $modifiedRulePackages[] = $rulePackage;
        }

        return $modifiedRulePackages;
    }
}