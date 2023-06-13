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
use Mosparo\Specifications\Specifications;
use Opis\JsonSchema\Validator;
use Symfony\Component\HttpClient\NativeHttpClient;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class RulesetHelper
{
    protected EntityManagerInterface $entityManager;

    protected UrlGeneratorInterface $router;

    protected HttpClientInterface $client;

    protected ConnectionHelper $connectionHelper;

    protected CleanupHelper $cleanupHelper;

    protected ProjectHelper $projectHelper;

    public function __construct(EntityManagerInterface $entityManager, UrlGeneratorInterface $router, HttpClientInterface $client, ConnectionHelper $connectionHelper, CleanupHelper $cleanupHelper, ProjectHelper $projectHelper)
    {
        $this->entityManager = $entityManager;
        $this->router = $router;
        $this->client = $client;
        $this->connectionHelper = $connectionHelper;
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
                $this->downloadRuleset($ruleset);
            }
        }

        $this->entityManager->flush();
    }

    public function downloadRuleset(Ruleset $ruleset): bool
    {
        if (!$this->connectionHelper->isDownloadPossible()) {
            throw new Exception('Downloading files from the internet is impossible because requirements need to be met. Please check the system page or the mosparo documentation.');
        }

        $client = $this->client;
        if ($this->connectionHelper->useNativeConnection()) {
            $client = new NativeHttpClient();
        }

        $this->verifyUrl($ruleset->getUrl());

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
        $args = [
            'headers' => [
                'X-mosparo-host' => $this->router->generate('dashboard', [], UrlGeneratorInterface::ABSOLUTE_URL),
                'X-mosparo-project-uuid' => $ruleset->getProject()->getUuid()
            ]
        ];

        $files = [];
        foreach ($urls as $fileType => $url) {
            $response = $client->request('GET', $url, $args);

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

        $isValid = $this->validateJsonSchema($files['content']);
        if (!$isValid) {
            throw new Exception('The ruleset content is not valid against the schema.');
        }

        $this->processRulesetContent($ruleset, $files['content']);

        return true;
    }

    protected function validateJsonSchema($content): bool
    {
        $json = json_decode($content);

        $validator = new Validator();
        $validator->resolver()->registerFile('http://schema.mosparo.io/ruleset.json', Specifications::getJsonSchemaPath(Specifications::JSON_SCHEMA_RULESET));
        $validator->resolver()->registerFile('http://schema.mosparo.io/rule.json', Specifications::getJsonSchemaPath(Specifications::JSON_SCHEMA_RULE));

        $result = $validator->validate($json, 'http://schema.mosparo.io/ruleset.json');

        return $result->isValid();
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
            return;
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

    protected function verifyUrl($url)
    {
        $path = parse_url($url, PHP_URL_PATH);

        if (empty($path)) {
            throw new Exception(sprintf('The URL to a ruleset needs to be fully qualified. There is no path in the URL "%s".', $url));
        }
    }
}