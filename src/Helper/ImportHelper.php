<?php

namespace Mosparo\Helper;

use Doctrine\ORM\EntityManagerInterface;
use Mosparo\Entity\Project;
use Mosparo\Entity\Rule;
use Mosparo\Entity\RuleItem;
use Mosparo\Entity\Ruleset;
use Mosparo\Exception\ImportException;
use Mosparo\Specifications\Specifications;
use Opis\JsonSchema\Validator;

class ImportHelper
{
    protected EntityManagerInterface $entityManager;

    protected ProjectHelper $projectHelper;

    protected string $importDirectory;

    public function __construct(EntityManagerInterface $entityManager, ProjectHelper $projectHelper, string $importDirectory)
    {
        $this->entityManager = $entityManager;
        $this->projectHelper = $projectHelper;
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

    public function simulateImport(string $token): array
    {
        $jobData = $this->loadJobData($token);

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
        $this->projectHelper->setActiveProject($activeProject);

        return [$jobData, $importData, $this->hasChanges($changes), $changes];
    }

    public function executeImport(string $token)
    {
        $jobData = $this->loadJobData($token);

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
        foreach ($changes as $sectionKey => $sectionChanges) {
            if (in_array($sectionKey, ['generalSettings', 'designSettings', 'securitySettings'])) {
                $this->executeProjectChanges($project, $sectionChanges);
            } else if ($sectionKey === 'rules') {
                $this->executeRuleChanges($sectionChanges);
            } else if ($sectionKey === 'rulesets') {
                $this->executeRulesetChanges($sectionChanges);
            }

            $this->entityManager->flush();
        }

        // Remove the two files since everything is done
        $this->removeFiles($token, $jobData['importDataFilename']);

        // Set the originally active project
        $this->projectHelper->setActiveProject($activeProject);
    }

    protected function loadImportData(array $jobData): array
    {
        $filePath = $this->importDirectory . '/' . $jobData['importDataFilename'];
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
            !empty($changes['rules'] ?? []) ||
            !empty($changes['rulesets'] ?? [])
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
        }

        if ($jobData['importRules'] && isset($importData['project']['rules'])) {
            $changes['rules'] = $this->findRuleChanges($importData['project']['rules'], $jobData['handlingExistingRules']);
        }

        if ($jobData['importRulesets'] && isset($importData['project']['rulesets'])) {
            $changes['rulesets'] = $this->findRulesetChanges($importData['project']['rulesets']);
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

        if (isset($importData['spamScore']) && $project->getSpamScore() !== $importData['spamScore']) {
            $changes[] = [
                'key' => 'spamScore',
                'oldValue' => $project->getSpamScore(),
                'newValue' => $importData['spamScore']
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
                            $storedItem->getSpamRatingFactor() !== (float) $item['rating']
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
                    $storedRule->getSpamRatingFactor() === (float) $rule['spamRatingFactor'] &&
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

    protected function findRulesetChanges(array $rulesets): array
    {
        $changes = [];
        $rulesetRepository = $this->entityManager->getRepository(Ruleset::class);

        foreach ($rulesets as $ruleset) {
            $storedRuleset = $rulesetRepository->findOneBy(['url' => $ruleset['url']]);

            $mode = 'add';
            $storedRulesetId = null;
            $storedRulesetName = null;
            if ($storedRuleset !== null) {
                if (
                    $storedRuleset->getName() === $ruleset['name'] &&
                    $storedRuleset->getSpamRatingFactor() === (float) $ruleset['spamRatingFactor'] &&
                    $storedRuleset->getStatus() === (bool) $ruleset['status']
                ) {
                    // Everything up to date, no change required.
                    continue;
                }

                $mode = 'modify';
                $storedRulesetId = $storedRuleset->getId();
                $storedRulesetName = $storedRuleset->getName();
            }

            $changes[] = [
                'mode' => $mode,
                'storedRuleset' => [
                    'id' => $storedRulesetId,
                    'name' => $storedRulesetName
                ],
                'importedRuleset' => $ruleset,
            ];
        }

        return $changes;
    }

    protected function executeProjectChanges(Project $project, array $sectionChanges)
    {
        foreach ($sectionChanges as $change) {
            $key = $change['key'];
            if (in_array($key, ['name', 'description', 'hosts', 'spamScore'])) {
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
                    case 'spamScore':
                        $project->setSpamScore($change['newValue']);
                    break;
                }
            } else {
                $project->setConfigValue($key, $change['newValue']);
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

    protected function executeRulesetChanges(array $sectionChanges)
    {
        $rulesetRepository = $this->entityManager->getRepository(Ruleset::class);

        foreach ($sectionChanges as $change) {
            $importedRuleset = $change['importedRuleset'];

            if ($change['mode'] === 'add') {
                $ruleset = new Ruleset();
                $ruleset->setUrl($importedRuleset['url']);

                $this->entityManager->persist($ruleset);
            } else if ($change['mode'] === 'modify') {
                $ruleset = $rulesetRepository->find($change['storedRuleset']['id']);
                if (!$ruleset) {
                    throw new ImportException('Stored ruleset not found.', ImportException::STORED_RULESET_NOT_FOUND);
                }
            }

            $ruleset->setName($importedRuleset['name']);
            $ruleset->setStatus((bool) $importedRuleset['status']);
            $ruleset->setSpamRatingFactor($importedRuleset['spamRatingFactor']);
        }
    }
}