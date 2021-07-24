<?php

namespace Mosparo\Helper;

use DateInterval;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Mosparo\Entity\Project;
use Mosparo\Entity\RulesetRuleItemCache;
use Mosparo\Exception;
use Mosparo\Entity\Ruleset;
use Mosparo\Entity\RulesetCache;
use Mosparo\Entity\RulesetRuleCache;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class RulesetHelper
{
    protected $entityManager;

    protected $client;

    protected $cleanupHelper;

    protected $projectHelper;

    public function __construct(EntityManagerInterface $entityManager, HttpClientInterface $client, CleanupHelper $cleanupHelper, ProjectHelper $projectHelper)
    {
        $this->entityManager = $entityManager;
        $this->client = $client;
        $this->cleanupHelper = $cleanupHelper;
        $this->projectHelper = $projectHelper;
    }

    public function downloadAll()
    {
        $projectRepository = $this->entityManager->getRepository(Project::class);
        $rulesetRepository = $this->entityManager->getRepository(Ruleset::class);

        foreach ($projectRepository->findAll() as $project) {
            $this->projectHelper->setActiveProject($project);

            foreach ($rulesetRepository->findBy(['status' => 1]) as $ruleset) {
                echo $ruleset->getName() . PHP_EOL;
                $this->downloadRuleset($ruleset);
            }
        }

        $this->entityManager->flush();
    }

    public function downloadRuleset(Ruleset $ruleset): bool
    {
        $rulesetCache = $ruleset->getRulesetCache();
        if ($rulesetCache !== null) {
            $refreshInterval = new DateInterval('PT' . $rulesetCache->getRefreshInterval() . 'S');
            $timeLeast = $rulesetCache->getRefreshedAt()->add($refreshInterval);

            // We're not allowed to download the ruleset again.
            if ($timeLeast > new DateTime()) {
                return false;
            }
        }

        $urls = [
            'content' => $ruleset->getUrl(),
            'hash' => $ruleset->getUrl() . '.sha256'
        ];
        $files = [];
        foreach ($urls as $fileType => $url) {
            $response = $this->client->request('GET', $url);

            if ($response->getStatusCode() !== 200) {
                throw new Exception('Cannot download the ruleset file.');
            }

            $files[$fileType] = $response->getContent();
        }

        $remoteHash = $files['hash'];
        if (strpos($remoteHash, ' ') !== false) {
            $remoteHash = substr($remoteHash, 0, strpos($remoteHash, ' '));
        }

        if (hash('sha256', $files['content']) !== $remoteHash) {
            throw new Exception('Hash verification failed.');
        }

        $this->processRulesetContent($ruleset, $files['content']);

        return true;
    }

    protected function processRulesetContent(Ruleset $ruleset, $content)
    {
        $data = json_decode($content, true);

        $rulesetCache = $ruleset->getRulesetCache();
        if ($rulesetCache === null) {
            $rulesetCache = new RulesetCache();
            $rulesetCache->setRuleset($ruleset);

            $this->entityManager->persist($rulesetCache);
        }

        if ($rulesetCache->getRefreshInterval() != $data['refreshInterval']) {
            $rulesetCache->setRefreshInterval($data['refreshInterval']);
        }

        $rulesetCache->setRefreshedAt(new DateTime());

        // We do nothing if the ruleset wasn't updated
        $updatedAt = new DateTime($data['lastUpdatedAt']);
        if ($updatedAt == $rulesetCache->getUpdatedAt()) {
            return false;
        }

        $rulesetCache->setUpdatedAt($updatedAt);

        $processedUuids = [];
        foreach ($data['rules'] as $rule) {
            $processedUuids[] = $rule['uuid'];

            $rulesetRuleCache = $rulesetCache->findRule($rule['uuid']);
            if ($rulesetRuleCache === null) {
                $rulesetRuleCache = new RulesetRuleCache();
                $rulesetRuleCache->setRulesetCache($rulesetCache);
                $rulesetRuleCache->setUuid($rule['uuid']);

                $this->entityManager->persist($rulesetRuleCache);
            }

            $rulesetRuleCache->setName($rule['name']);
            $rulesetRuleCache->setDescription($rule['description']);
            $rulesetRuleCache->setType($rule['type']);
            $rulesetRuleCache->setSpamRatingFactor($rule['spamRatingFactor']);

            $processedItemUuids = [];
            foreach ($rule['items'] as $item) {
                $processedItemUuids[] = $item['uuid'];

                $rulesetRuleItemCache = $rulesetRuleCache->findItem($item['uuid']);
                if ($rulesetRuleItemCache === null) {
                    $rulesetRuleItemCache = new RulesetRuleItemCache();
                    $rulesetRuleItemCache->setRulesetRuleCache($rulesetRuleCache);
                    $rulesetRuleItemCache->setUuid($item['uuid']);

                    $this->entityManager->persist($rulesetRuleItemCache);
                }

                $rulesetRuleItemCache->setType($item['type']);
                $rulesetRuleItemCache->setValue($item['value']);
                $rulesetRuleItemCache->setSpamRatingFactor((float) $item['rating']);
            }

            // Remove all rule items which are not in the data anymore
            foreach ($rulesetRuleCache->getItems() as $item) {
                if (!in_array($item->getUuid(), $processedItemUuids)) {
                    $this->entityManager->remove($item);
                }
            }
        }

        // Remove all rules which are not in the data anymore
        foreach ($rulesetCache->getRules() as $rule) {
            if (!in_array($rule->getUuid(), $processedUuids)) {
                $this->entityManager->remove($rule);
            }
        }
    }
}