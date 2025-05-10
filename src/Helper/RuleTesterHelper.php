<?php

namespace Mosparo\Helper;

use Doctrine\ORM\EntityManagerInterface;
use Mosparo\Entity\Rule;
use Mosparo\Entity\RulePackage;
use Mosparo\Entity\Submission;
use Mosparo\Exception;
use Mosparo\Rule\RuleTypeManager;
use Mosparo\Rule\Type\RuleTypeInterface;
use Mosparo\Util\TokenGenerator;

class RuleTesterHelper
{
    protected EntityManagerInterface $entityManager;

    protected RuleTypeManager $ruleTypeManager;

    protected ProjectHelper $projectHelper;

    protected TokenGenerator $tokenGenerator;

    protected RulePackageHelper $rulePackageHelper;

    protected GeoIp2Helper $geoIp2Helper;

    protected RuleCacheHelper $ruleCacheHelper;

    protected bool $prepareRulesInSharedCache;

    protected array $rules = [];
    protected array $ruleTesters = [];

    public function __construct(
        EntityManagerInterface $entityManager,
        RuleTypeManager $ruleTypeManager,
        ProjectHelper $projectHelper,
        TokenGenerator $tokenGenerator,
        RulePackageHelper $rulePackageHelper,
        GeoIp2Helper $geoIp2Helper,
        RuleCacheHelper $ruleCacheHelper,
        bool $prepareRulesInSharedCache,
    ) {
        $this->entityManager = $entityManager;
        $this->ruleTypeManager = $ruleTypeManager;
        $this->projectHelper = $projectHelper;
        $this->tokenGenerator = $tokenGenerator;
        $this->rulePackageHelper = $rulePackageHelper;
        $this->geoIp2Helper = $geoIp2Helper;
        $this->ruleCacheHelper = $ruleCacheHelper;
        $this->prepareRulesInSharedCache = $prepareRulesInSharedCache;
    }

    public function simulateRequest($value, $type = 'textField', $useRules = true, $useRulePackages = true): Submission
    {
        $translatedFieldType = [
            'textField' => 'text',
            'emailField' => 'email',
            'urlField' => 'url',
        ];
        $data = [];
        if (in_array($type, ['textField', 'emailField', 'urlField'])) {
            $fieldType = $translatedFieldType[$type] ?? 'text';
            $data = [
                'formData' => [
                    [
                        'name' => 'test-field',
                        'value' => $value,
                        'fieldPath' => 'input[' . $fieldType . '].test-field'
                    ]
                ]
            ];
        } else if ($type === 'textarea') {
            $data = [
                'formData' => [
                    [
                        'name' => 'test-field',
                        'value' => $value,
                        'fieldPath' => 'textarea.test-field'
                    ]
                ]
            ];
        } else if ($type === 'userAgent') {
            $data = [
                'client' => [
                    [
                        'name' => 'userAgent',
                        'value' => $value,
                        'fieldPath' => 'userAgent'
                    ]
                ]
            ];
        } else if ($type === 'ipAddress') {
            $data = [
                'client' => [
                    [
                        'name' => 'ipAddress',
                        'value' => $value,
                        'fieldPath' => 'ipAddress'
                    ]
                ]
            ];

            $ipLocalization = $this->geoIp2Helper->locateIpAddress($value);
            if ($ipLocalization !== false) {
                $data['client'][] = [
                    'name' => 'asNumber',
                    'value' => $ipLocalization->getAsNumber(),
                    'fieldPath' => 'asNumber'
                ];
                $data['client'][] = [
                    'name' => 'asOrganization',
                    'value' => $ipLocalization->getAsOrganization(),
                    'fieldPath' => 'asOrganization'
                ];
                $data['client'][] = [
                    'name' => 'country',
                    'value' => $ipLocalization->getCountry(),
                    'fieldPath' => 'country'
                ];
            }
        }

        // Do not save the submission since it's only a simulation.
        $submission = new Submission();
        $submission->setData($data);

        $this->checkRequest($submission, [], null, $useRules, $useRulePackages);

        return $submission;
    }

    public function checkRequest(Submission $submission, array $securitySettings = [], $type = null, $useRules = true, $useRulePackages = true): void
    {
        $ruleArgs = ['status' => 1];
        if ($type !== null) {
            $ruleArgs['type'] = $type;
        }

        // Load the rules
        $this->loadRules($ruleArgs, $useRules, $useRulePackages);

        // Load the rule testers
        $this->loadRuleTesters();

        // Check the rules
        $results = $this->checkRules($submission->getData());

        // Analyze the results
        $this->analyzeResults($submission, $results, $securitySettings);
    }

    protected function loadRules(array $ruleArgs, $useRules = true, $useRulePackages = true): void
    {
        $this->rules = [];

        // If enabled, try to load the rules from the cache, but only if rules and rule packages are used.
        if ($this->prepareRulesInSharedCache && $useRules && $useRulePackages) {
            $this->rules = $this->ruleCacheHelper->loadRulesFromCache();
        }

        // If there were no rules in the cache, load the rules from the database
        if (!$this->rules) {
            if ($useRules) {
                $ruleRepository = $this->entityManager->getRepository(Rule::class);
                $this->rules = $ruleRepository->findBy($ruleArgs);
            }

            if ($useRulePackages) {
                $rulePackageRepository = $this->entityManager->getRepository(RulePackage::class);
                $rulePackages = $rulePackageRepository->findBy(['status' => 1]);

                foreach ($rulePackages as $rulePackage) {
                    try {
                        $result = $this->rulePackageHelper->fetchRulePackage($rulePackage);

                        if ($result) {
                            $this->entityManager->flush();
                        }
                    } catch (Exception $e) {
                        // Do nothing
                    }

                    $rulePackageCache = $rulePackage->getRulePackageCache();
                    if ($rulePackageCache === null) {
                        continue;
                    }

                    $rules = $rulePackageCache->getRules();
                    foreach ($rules as $rule) {
                        if (isset($ruleArgs['type']) && !in_array($rule->getType(), $ruleArgs['type'])) {
                            continue;
                        }

                        $this->rules[] = $rule;
                    }
                }
            }

            // Store the rules in the cache, if enabled. We only store the rules if rules and rule packages are used.
            if ($this->prepareRulesInSharedCache && $useRules && $useRulePackages) {
                $this->ruleCacheHelper->storeRulesInCache($this->rules);
            }
        }
    }

    protected function checkRules(array $data): array
    {
        $results = [];
        foreach ($data as $groupKey => $groupData) {
            foreach ($groupData as $fieldData) {
                $path = $groupKey . '.' . $fieldData['fieldPath'];

                $issues = $this->checkRulesForField($path, $fieldData);

                if ($issues) {
                    $results[$path] = $issues;
                }
            }
        }

        return $results;
    }

    protected function checkRulesForField($path, $fieldData): array
    {
        $issues = [];
        foreach ($this->rules as $rule) {
            $ruleType = $this->ruleTypeManager->getRuleType($rule->getType());
            if (!$this->isRuleTypeApplicable($ruleType, $path)) {
                continue;
            }

            $ruleTester = $this->ruleTesters[$rule->getType()];

            $value = $fieldData['value'] ?? '';
            if (is_array($value)) {
                $result = [];
                foreach ($value as $key => $subValue) {
                    $subResult = $ruleTester->validateData($fieldData['name'], $subValue, $rule);

                    if (count($subResult) > 0) {
                        $result = array_merge($result, $subResult);
                    }
                }
            } else {
                $result = $ruleTester->validateData($fieldData['name'], $value, $rule);
            }

            if (count($result) > 0) {
                $issues = array_merge($issues, $result);
            }
        }

        return $issues;
    }

    protected function isRuleTypeApplicable(RuleTypeInterface $ruleType, $path): bool
    {
        foreach ($ruleType->getTargetFieldKeys() as $fieldKey) {
            if (str_starts_with($path, $fieldKey)) {
                return true;
            }
        }

        return false;
    }

    protected function loadRuleTesters(): void
    {
        foreach ($this->rules as $rule) {
            if (isset($this->ruleTesters[$rule->getType()])) {
                continue;
            }

            $ruleType = $this->ruleTypeManager->getRuleType($rule->getType());

            $ruleTesterClass = $ruleType->getTesterClass();
            $ruleTester = new $ruleTesterClass();
            $this->ruleTesters[$rule->getType()] = $ruleTester;
        }
    }

    protected function analyzeResults(Submission $submission, array $results, array $securitySettings = []): void
    {
        $score = 0;

        foreach ($results as $issues) {
            foreach ($issues as $issue) {
                $score += $issue['rating'];
            }
        }

        $activeProject = $this->projectHelper->getActiveProject();

        $spamStatus = $activeProject->getStatus();
        $spamScore = $activeProject->getSpamScore();
        if ($securitySettings['overrideSpamDetection'] ?? false) {
            $spamStatus = $securitySettings['spamStatus'] ?? $spamStatus;
            $spamScore = $securitySettings['spamScore'] ?? $spamScore;
        }

        $submission->setSpamRating($score);
        $submission->setSpamDetectionRating($spamScore);
        $submission->setMatchedRuleItems($results);

        if ($score >= $submission->getSpamDetectionRating() && $spamStatus) {
            $submission->setSpam(true);
        } else {
            $submission->setSpam(false);
            $submission->setValidationToken($this->tokenGenerator->generateToken());
        }
    }
}