<?php

namespace Mosparo\Helper;

use DateInterval;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Mosparo\Entity\Project;
use Mosparo\Entity\RulePackageRuleItemCache;
use Mosparo\Enum\RulePackageType;
use Mosparo\Exception;
use Mosparo\Entity\RulePackage;
use Mosparo\Entity\RulePackageCache;
use Mosparo\Entity\RulePackageRuleCache;
use Mosparo\Specifications\Specifications;
use Opis\JsonSchema\Validator;
use Symfony\Component\HttpClient\NativeHttpClient;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class RulePackageHelper
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

    public function fetchAll(): void
    {
        $projectRepository = $this->entityManager->getRepository(Project::class);
        $rulePackageRepository = $this->entityManager->getRepository(RulePackage::class);

        foreach ($projectRepository->findAll() as $project) {
            $this->projectHelper->setActiveProject($project);

            foreach ($rulePackageRepository->findBy(['status' => 1]) as $rulePackage) {
                if (in_array($rulePackage->getType(), RulePackageType::automaticTypes())) {
                    $this->fetchRulePackage($rulePackage);
                }
            }

            $this->entityManager->flush();
        }
    }

    public function fetchRulePackages(array $rulePackages): void
    {
        foreach ($rulePackages as $rulePackage) {
            if ($rulePackage->isActive() && in_array($rulePackage->getType(), RulePackageType::automaticTypes())) {
                $this->fetchRulePackage($rulePackage);
            }
        }

        $this->entityManager->flush();
    }

    public function fetchRulePackage(RulePackage $rulePackage): bool
    {
        if (!$rulePackage->isActive() || !in_array($rulePackage->getType(), RulePackageType::automaticTypes())) {
            return false;
        }

        $rulePackageCache = $rulePackage->getRulePackageCache();
        if ($rulePackageCache !== null) {
            $refreshInterval = new DateInterval('PT' . $rulePackageCache->getRefreshInterval() . 'S');
            $timeLeast = (clone $rulePackageCache->getRefreshedAt())->add($refreshInterval);

            // We're not allowed to download the rulePackage again.
            if ($timeLeast > new DateTime()) {
                return false;
            }
        }

        $files = [];
        if ($rulePackage->getType() === RulePackageType::AUTOMATICALLY_FROM_URL) {
            $files = $this->downloadFiles($rulePackage);
        } else if ($rulePackage->getType() === RulePackageType::AUTOMATICALLY_FROM_FILE) {
            $files = $this->loadFiles($rulePackage);
        }

        $fileHash = $files['hash'];
        if (strpos($fileHash, ' ') !== false) {
            $fileHash = substr($fileHash, 0, strpos($fileHash, ' '));
        }

        if (hash('sha256', $files['content']) !== $fileHash) {
            throw new Exception('Hash verification failed.');
        }

        $this->validateAndProcessContent($rulePackage, $files['content']);

        return true;
    }

    protected function downloadFiles(RulePackage $rulePackage): array
    {
        if (!$this->connectionHelper->isDownloadPossible()) {
            throw new Exception('Downloading files from the internet is impossible because requirements need to be met. Please check the system page or the mosparo documentation.');
        }

        $client = $this->client;
        if ($this->connectionHelper->useNativeConnection()) {
            $client = new NativeHttpClient();
        }

        $this->verifyUrl($rulePackage->getSource());

        $urls = [
            'content' => $rulePackage->getSource(),
            'hash' => $rulePackage->getSource() . '.sha256'
        ];
        $args = [
            'headers' => [
                'X-mosparo-host' => $this->router->generate('dashboard', [], UrlGeneratorInterface::ABSOLUTE_URL),
                'X-mosparo-project-uuid' => $rulePackage->getProject()->getUuid()
            ]
        ];

        $files = [];
        foreach ($urls as $fileType => $url) {
            $response = $client->request('GET', $url, $args);

            if ($response->getStatusCode() !== 200) {
                throw new Exception('Cannot download the rulePackage file.');
            }

            $files[$fileType] = $response->getContent();
        }

        return $files;
    }

    protected function loadFiles(RulePackage $rulePackage): array
    {
        $urls = [
            'content' => $rulePackage->getSource(),
            'hash' => $rulePackage->getSource() . '.sha256'
        ];

        $files = [];
        foreach ($urls as $fileType => $filePath) {
            if (!file_exists($filePath)) {
                throw new Exception(sprintf('The given file path "%s" does not exist.', $filePath));
            }

            $files[$fileType] = file_get_contents($filePath);
        }

        return $files;
    }

    public function validateAndProcessContent(RulePackage $rulePackage, string $content): void
    {
        $isValid = $this->validateJsonSchema($content);
        if (!$isValid) {
            throw new Exception('The rulePackage content is not valid against the schema.');
        }

        $this->processRulePackageContent($rulePackage, $content);
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

    protected function processRulePackageContent(RulePackage $rulePackage, $content): void
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
            return;
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
    }

    protected function verifyUrl($url): void
    {
        $path = parse_url($url, PHP_URL_PATH);

        if (empty($path)) {
            throw new Exception(sprintf('The URL to a rule package needs to be fully qualified. There is no path in the URL "%s".', $url));
        }
    }
}