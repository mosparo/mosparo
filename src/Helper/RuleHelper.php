<?php

namespace Mosparo\Helper;

use Doctrine\ORM\EntityManagerInterface;
use Mosparo\Entity\Rule;
use Mosparo\Entity\Submission;
use Mosparo\Rule\RuleTypeManager;
use Mosparo\Rule\Type\RuleTypeInterface;
use Mosparo\Util\TokenGenerator;

class RuleHelper
{
    protected $entityManager;

    protected $ruleTypeManager;

    protected $activeProjectHelper;

    protected $tokenGenerator;

    protected $rules = [];
    protected $ruleTesters = [];

    public function __construct(EntityManagerInterface $entityManager, RuleTypeManager $ruleTypeManager, ActiveProjectHelper $activeProjectHelper, TokenGenerator $tokenGenerator)
    {
        $this->entityManager = $entityManager;
        $this->ruleTypeManager = $ruleTypeManager;
        $this->activeProjectHelper = $activeProjectHelper;
        $this->tokenGenerator = $tokenGenerator;
    }

    public function checkRequest(Submission $submission, $type = null)
    {
        $ruleArgs = ['status' => 1];
        if ($type !== null) {
            $ruleArgs['type'] = $type;
        }

        $ruleRepository = $this->entityManager->getRepository(Rule::class);
        $this->rules = $ruleRepository->findBy($ruleArgs);

        // Load the rule testers
        $this->loadRuleTesters();

        // Check the rules
        $results = $this->checkRules($submission->getData());

        // Analyze the results
        $this->analyzeResults($submission, $results);
    }

    protected function checkRules(array $data)
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

    protected function checkRulesForField($path, $fieldData)
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

    protected function isRuleTypeApplicable(RuleTypeInterface $ruleType, $path)
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

        foreach ($results as $fieldPath => $issues) {
            foreach ($issues as $issue) {
                $score += $issue['rating'];
            }
        }

        $activeProject = $this->activeProjectHelper->getActiveProject();

        $submission->setSpamRating($score);
        $submission->setSpamDetectionRating($activeProject->getSpamScore());
        $submission->setMatchedRuleItems($results);

        if ($score >= $submission->getSpamDetectionRating()) {
            $submission->setSpam(true);
        } else {
            $submission->setSpam(false);
            $submission->setValidationToken($this->tokenGenerator->generateToken());
        }
    }
}