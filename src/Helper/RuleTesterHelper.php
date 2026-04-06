<?php

namespace Mosparo\Helper;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\QueryBuilder;
use Mosparo\Entity\DetectionResult;
use Mosparo\Entity\Rule;
use Mosparo\Entity\RuleItem;
use Mosparo\Entity\RulePackageRuleCache;
use Mosparo\Entity\RulePackageRuleItemCache;
use Mosparo\Entity\Submission;
use Mosparo\Entity\SubmissionRule;
use Mosparo\Rules\FieldRule\RuleItemIterator;
use Mosparo\Rules\FieldRule\RuleTypeManager;
use Mosparo\Rules\FieldRule\Type\RuleTypeInterface;
use Mosparo\Rules\SubmissionRule\SubmissionRuleManager;
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

    protected SubmissionRuleManager $submissionRuleManager;

    protected array $rules = [];

    protected array $results = [];

    public function __construct(
        EntityManagerInterface $entityManager,
        RuleTypeManager $ruleTypeManager,
        ProjectHelper $projectHelper,
        TokenGenerator $tokenGenerator,
        RulePackageHelper $rulePackageHelper,
        GeoIp2Helper $geoIp2Helper,
        RuleCacheHelper $ruleCacheHelper,
        SubmissionRuleManager $submissionRuleManager
    ) {
        $this->entityManager = $entityManager;
        $this->ruleTypeManager = $ruleTypeManager;
        $this->projectHelper = $projectHelper;
        $this->tokenGenerator = $tokenGenerator;
        $this->rulePackageHelper = $rulePackageHelper;
        $this->geoIp2Helper = $geoIp2Helper;
        $this->ruleCacheHelper = $ruleCacheHelper;
        $this->submissionRuleManager = $submissionRuleManager;
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

        $this->checkRequest($submission, [], $useRules, $useRulePackages);

        return $submission;
    }

    public function checkRequest(Submission $submission, array $securitySettings = [], bool $useRules = true,  bool $useRulePackages = true, bool $useSubmissionRules = true): void
    {
        $submission->setDetectionResult((new DetectionResult())->setSubmission($submission));
        $this->loadRuleTesters();

        // Check the field rules
        foreach ($submission->getData() as $groupKey => $groupData) {
            // We do not check the metadata
            if ($groupKey === 'metadata') {
                continue;
            }

            foreach ($groupData as $fieldData) {
                if (is_array($fieldData['value'])) {
                    foreach ($fieldData['value'] as $subValue) {
                        if (!trim($subValue)) {
                            continue;
                        }

                        $this->checkFieldData($submission->getDetectionResult(), $groupKey, $fieldData, $subValue, $useRules, $useRulePackages);
                    }
                } else {
                    if (!trim($fieldData['value'])) {
                        continue;
                    }

                    $this->checkFieldData($submission->getDetectionResult(), $groupKey, $fieldData, $fieldData['value'], $useRules, $useRulePackages);
                }
            }
        }

        // Check the submission rules
        if ($useSubmissionRules) {
            $submissionRuleRepository = $this->entityManager->getRepository(SubmissionRule::class);
            $enabledSubmissionRules = $submissionRuleRepository->findBy(['enabled' => true]);
            foreach ($enabledSubmissionRules as $storedSubmissionRule) {
                $submissionRule = $this->submissionRuleManager->getRule($storedSubmissionRule->getKey());
                if (!$submissionRule) {
                    continue;
                }

                $submissionRule->checkSubmission($storedSubmissionRule, $submission);
            }
        }

        // Analyze the results
        $this->analyzeResults($submission, $securitySettings);
    }

    protected function checkFieldData(DetectionResult $detectionResult, string $groupKey, array $fieldData, mixed $value, bool $useRules = true,  bool$useRulePackages = true)
    {
        $value = strtolower($value);
        $path = $groupKey . '.' . $fieldData['fieldPath'];

        $qb = $this->entityManager->createQueryBuilder();
        $qb
            ->select('i');

        $fromCache = false;
        $storedRuleItemIds = $this->ruleCacheHelper->getRuleItemIdsForValue($value);

        if ($storedRuleItemIds) {
            $fromCache = true;

            $ruleItemIterator = new RuleItemIterator($this->entityManager, $qb, $useRules, $useRulePackages, $storedRuleItemIds);
        } else {
            $orExpr = $this->buildExpressions($qb, $groupKey, $fieldData, $value);

            // If we have no expressions, we do not have to do anything with this field.
            if ($orExpr === null) {
                return;
            }

            $qb->andWhere($orExpr);

            $ruleItemIterator = new RuleItemIterator($this->entityManager, $qb, $useRules, $useRulePackages);
        }

        $processedItemIds = ['ri' => [], 'rpric' => []];
        foreach ($ruleItemIterator as $item) {
            if ($item instanceof RuleItem) {
                $processedItemIds['ri'][] = $item->getId();
            } else if ($item instanceof RulePackageRuleItemCache) {
                $processedItemIds['rpric'][] = $item->getId();
            }

            $rule = $item->getParent();
            if ($rule instanceof Rule && !$rule->getStatus()) {
                // Ignore the rule item because the rule is disabled
                continue;
            } else if ($rule instanceof RulePackageRuleCache && !$rule->getRulePackageCache()->getRulePackage()->getStatus()) {
                // Ignore the rule item because the rule package is disabled
                continue;
            }

            $tester = $this->ruleTesters[$rule->getType()] ?? null;

            if (!$tester) {
                continue;
            }

            $result = $tester->validateData($fieldData['name'], $value, $item);

            if ($result) {
                $detectionResult->addMatchedFieldRuleItem($path, $result);
            }

            $ruleItemIterator->detach($item);
        }

        if (!$fromCache) {
            $this->ruleCacheHelper->storeRuleItemsForValue($value, $processedItemIds);
        }
    }

    protected function buildExpressions(QueryBuilder $qb, string $groupKey, array $fieldData, mixed $value): ?Expr\Orx
    {
        $orExpr = $qb->expr()->orX();

        foreach ($this->ruleTypeManager->getRuleTypes() as $ruleType) {
            $path = $groupKey . '.' . $fieldData['fieldPath'];
            if (!$this->isRuleTypeApplicable($ruleType, $path)) {
                continue;
            }

            $tester = $this->ruleTesters[$ruleType->getKey()] ?? null;
            if (!$tester) {
                continue;
            }

            $tester->buildExpressions($qb, $orExpr, $fieldData, $value);
        }

        if ($orExpr->count() === 0) {
            return null;
        }

        return $orExpr;
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
        foreach ($this->ruleTypeManager->getRuleTypes() as $ruleType) {
            $ruleTesterClass = $ruleType->getTesterClass();
            $ruleTester = new $ruleTesterClass($this->entityManager);
            $this->ruleTesters[$ruleType->getKey()] = $ruleTester;
        }
    }

    protected function analyzeResults(Submission $submission, array $securitySettings = []): void
    {
        $score = $submission->getDetectionResult()->countPoints();
        $activeProject = $this->projectHelper->getActiveProject();

        $spamStatus = $activeProject->getStatus();
        $spamScore = $activeProject->getSpamScore();
        if ($securitySettings['overrideSpamDetection'] ?? false) {
            $spamStatus = $securitySettings['spamStatus'] ?? $spamStatus;
            $spamScore = $securitySettings['spamScore'] ?? $spamScore;
        }

        $submission->setSpamRating($score);
        $submission->setSpamDetectionRating($spamScore);

        if ($score >= $submission->getSpamDetectionRating() && $spamStatus) {
            $submission->setSpam(true);
        } else {
            $submission->setSpam(false);
        }

        if (!$submission->isSpam() || $activeProject->isSilentModeEnabled()) {
            $submission->setValidationToken($this->tokenGenerator->generateToken());
        }
    }
}