<?php

namespace Mosparo\RulePackage\Importer;

use Doctrine\ORM\EntityManagerInterface;
use Mosparo\Entity\RulePackage;
use Mosparo\Entity\RulePackageCache;
use Mosparo\Entity\RulePackageRuleCache;
use Mosparo\Entity\RulePackageRuleItemCache;
use Mosparo\Entity\RulePackageProcessingJob;
use Mosparo\Enum\RulePackageResult;
use Mosparo\Exception;
use Mosparo\RulePackage\ImporterInterface;
use Mosparo\Specifications\Specifications;
use Opis\JsonSchema\Validator;
use DateTime;

class JsonImporter implements ImporterInterface
{
    protected EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function importRulePackage(RulePackageProcessingJob $processingJob, string $filePath, string $cacheDirectory): RulePackageResult
    {
        // Load the JSON content
        $content = file_get_contents($filePath);

        $isValid = $this->validateJsonSchema($content);
        if (!$isValid) {
            throw new Exception('The rule package content is not valid against the schema.');
        }

        return $this->processRulePackageContent($processingJob->getRulePackage(), $content);
    }

    protected function validateJsonSchema($content): bool
    {
        $json = json_decode($content);

        $validator = new Validator();
        $validator->resolver()->registerFile('http://schema.mosparo.io/rule-package.json', Specifications::getJsonSchemaPath(Specifications::JSON_SCHEMA_RULE_PACKAGE));
        $validator->resolver()->registerFile('http://schema.mosparo.io/rule.json', Specifications::getJsonSchemaPath(Specifications::JSON_SCHEMA_RULE));

        $result = $validator->validate($json, 'http://schema.mosparo.io/rule-package.json');

        return $result->isValid();
    }

    protected function processRulePackageContent(RulePackage $rulePackage, $content): RulePackageResult
    {
        $data = json_decode($content, true);

        $rulePackageCache = $rulePackage->getRulePackageCache();
        if ($rulePackageCache === null) {
            $rulePackageCache = new RulePackageCache();
            $rulePackageCache->setRulePackage($rulePackage);
            $rulePackageCache->setProject($rulePackage->getProject());
            $rulePackage->setRulePackageCache($rulePackageCache);

            $this->entityManager->persist($rulePackageCache);
        }

        if ($rulePackageCache->getRefreshInterval() != $data['refreshInterval']) {
            $rulePackageCache->setRefreshInterval($data['refreshInterval']);
        }

        $rulePackageCache->setRefreshedAt(new DateTime());

        // We do nothing if the rulePackage wasn't updated
        $updatedAt = new DateTime($data['lastUpdatedAt']);
        if ($updatedAt == $rulePackageCache->getUpdatedAt()) {
            return RulePackageResult::ALREADY_UP_TO_DATE;
        }

        // Adjust the timezone from the rule package content to the one configured on this server
        if ($updatedAt->getTimezone() !== $rulePackageCache->getRefreshedAt()->getTimezone()) {
            $updatedAt->setTimezone($rulePackageCache->getRefreshedAt()->getTimezone());
        }

        $rulePackageCache->setUpdatedAt($updatedAt);

        $processedUuids = [];
        foreach ($data['rules'] as $rule) {
            $processedUuids[] = $rule['uuid'];

            $rulePackageRuleCache = $rulePackageCache->findRule($rule['uuid']);
            if ($rulePackageRuleCache === null) {
                $rulePackageRuleCache = new RulePackageRuleCache();
                $rulePackageRuleCache->setRulePackageCache($rulePackageCache);
                $rulePackageRuleCache->setProject($rulePackage->getProject());
                $rulePackageRuleCache->setUuid($rule['uuid']);

                $this->entityManager->persist($rulePackageRuleCache);
            }

            $rulePackageRuleCache->setName($rule['name']);
            $rulePackageRuleCache->setDescription($rule['description'] ?? '');
            $rulePackageRuleCache->setType($rule['type']);

            $rating = null;
            if ($rule['spamRatingFactor']) {
                $rating = (float) $rule['spamRatingFactor'];
            }
            $rulePackageRuleCache->setSpamRatingFactor($rating);

            $processedItemUuids = [];
            foreach ($rule['items'] as $item) {
                $processedItemUuids[] = $item['uuid'];

                $rulePackageRuleItemCache = $rulePackageRuleCache->findItem($item['uuid']);
                if ($rulePackageRuleItemCache === null) {
                    $rulePackageRuleItemCache = new RulePackageRuleItemCache();
                    $rulePackageRuleItemCache->setRulePackageRuleCache($rulePackageRuleCache);
                    $rulePackageRuleItemCache->setProject($rulePackage->getProject());
                    $rulePackageRuleItemCache->setUuid($item['uuid']);

                    $this->entityManager->persist($rulePackageRuleItemCache);
                }

                $rulePackageRuleItemCache->setType($item['type']);
                $rulePackageRuleItemCache->setValue($item['value']);

                $rating = null;
                if ($item['rating']) {
                    $rating = (float) $item['rating'];
                }
                $rulePackageRuleItemCache->setSpamRatingFactor($rating);
            }

            // Remove all rule items which are not in the data anymore
            foreach ($rulePackageRuleCache->getItems() as $item) {
                if (!in_array($item->getUuid(), $processedItemUuids)) {
                    $this->entityManager->remove($item);
                }
            }
        }

        // Remove all rules which are not in the data anymore
        foreach ($rulePackageCache->getRules() as $rule) {
            if (!in_array($rule->getUuid(), $processedUuids)) {
                $this->entityManager->remove($rule);
            }
        }

        $this->entityManager->flush();

        return RulePackageResult::COMPLETED;
    }
}
