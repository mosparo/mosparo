<?php

namespace Mosparo\Helper;

use Doctrine\ORM\EntityManagerInterface;
use Mosparo\Entity\Rule;
use Mosparo\Entity\Ruleset;
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

    protected RulesetHelper $rulesetHelper;

    protected array $rules = [];
    protected array $ruleTesters = [];

    public function __construct(
        EntityManagerInterface $entityManager,
        RuleTypeManager $ruleTypeManager,
        ProjectHelper $projectHelper,
        TokenGenerator $tokenGenerator,
        RulesetHelper $rulesetHelper
    ) {
        $this->entityManager = $entityManager;
        $this->ruleTypeManager = $ruleTypeManager;
        $this->projectHelper = $projectHelper;
        $this->tokenGenerator = $tokenGenerator;
        $this->rulesetHelper = $rulesetHelper;
    }

    public function checkRequest(Submission $submission, $type = null)
    {
        $ruleArgs = ['status' => 1];
        if ($type !== null) {
            $ruleArgs['type'] = $type;
        }

        // Load the rules
        $this->loadRules($ruleArgs);

        // Load the rule testers
        $this->loadRuleTesters();

        // Check the rules
        $results = $this->checkRules($submission->getData());

        // Analyze the results
        $this->analyzeResults($submission, $results);
    }

    protected function loadRules(array $ruleArgs)
    {
        $ruleRepository = $this->entityManager->getRepository(Rule::class);
        $this->rules = $ruleRepository->findBy($ruleArgs);

        $rulesetRepository = $this->entityManager->getRepository(Ruleset::class);
        $rulesets = $rulesetRepository->findBy(['status' => 1]);

        foreach ($rulesets as $ruleset) {
            try {
                $result = $this->rulesetHelper->downloadRuleset($ruleset);

                if ($result) {
                    $this->entityManager->flush($ruleset);
                }
            } catch (Exception $e) {
                // Do nothing
            }

            $rulesetCache = $ruleset->getRulesetCache();
            if ($rulesetCache === null) {
                continue;
            }

            foreach ($rulesetCache->getRules() as $rule) {
                if (isset($ruleArgs['type']) && !in_array($rule->getType(), $ruleArgs['type'])) {
                    continue;
                }

                $this->rules[] = $rule;
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

            $result = $ruleTester->validateData($fieldData['name'], $fieldData['value'], $rule);

            if (count($result) > 0) {
                $issues = array_merge($issues, $result);
            }
        }

        return $issues;
    }

    protected function isRuleTypeApplicable(RuleTypeInterface $ruleType, $path): bool
    {
        foreach ($ruleType->getTargetFieldKeys() as $fieldKey) {
            if (strpos($path, $fieldKey) === 0) {
                return true;
            }
        }

        return false;
    }

    protected function loadRuleTesters()
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

    protected function analyzeResults(Submission $submission, $results)
    {
        $score = 0;

        foreach ($results as $issues) {
            foreach ($issues as $issue) {
                $score += $issue['rating'];
            }
        }

        $activeProject = $this->projectHelper->getActiveProject();

        $submission->setSpamRating($score);
        $submission->setSpamDetectionRating($activeProject->getSpamScore());
        $submission->setMatchedRuleItems($results);

        if ($score >= $submission->getSpamDetectionRating() && $activeProject->getStatus()) {
            $submission->setSpam(true);
        } else {
            $submission->setSpam(false);
            $submission->setValidationToken($this->tokenGenerator->generateToken());
        }
    }
}