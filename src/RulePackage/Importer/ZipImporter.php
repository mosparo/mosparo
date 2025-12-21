<?php

namespace Mosparo\RulePackage\Importer;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\ORM\EntityManagerInterface;
use Monolog\Handler\NullHandler;
use Monolog\Logger;
use Mosparo\Entity\RulePackageCache;
use Mosparo\Entity\RulePackageRuleCache;
use Mosparo\Entity\RulePackageRuleItemCache;
use Mosparo\Entity\RulePackageProcessingJob;
use Mosparo\Enum\RulePackageResult;
use Mosparo\Exception;
use Mosparo\Helper\RulePackageHelper;
use Mosparo\RulePackage\ImporterInterface;
use Mosparo\Specifications\Specifications;
use Opis\JsonSchema\Validator;
use DateTime;
use Psr\Log\LoggerInterface;
use Symfony\Component\Filesystem\Filesystem;

class ZipImporter implements ImporterInterface
{
    protected RulePackageHelper $rulePackageHelper;

    protected EntityManagerInterface $entityManager;

    protected LoggerInterface $logger;

    protected string $rulePackageDirectory;

    protected ?int $startTime = null;

    protected ?int $maxSeconds = null;

    protected array $rulePackageRuleCache = [];

    public function __construct(RulePackageHelper $rulePackageHelper, EntityManagerInterface $entityManager, LoggerInterface $logger, string $rulePackageDirectory, ?int $startTime = null, ?int $maxSeconds = null)
    {
        $this->rulePackageHelper = $rulePackageHelper;
        $this->entityManager = $entityManager;
        $this->logger = $logger;
        $this->rulePackageDirectory = $rulePackageDirectory;
        $this->startTime = $startTime;
        $this->maxSeconds = $maxSeconds;
    }

    public function importRulePackage(RulePackageProcessingJob $processingJob, string $filePath, string $cacheDirectory): RulePackageResult
    {
        if ($this->logger instanceof Logger) {
            $this->logger->pushHandler(new NullHandler());
        }

        // Remove the middlewares to prevent memory issues, especially in DEV environment.
        $middlewares = $this->entityManager->getConnection()->getConfiguration()->getMiddlewares();
        $this->entityManager->getConnection()->getConfiguration()->setMiddlewares([]);

        $batchSize = 1000;
        $filesystem = new Filesystem();

        // If we do not get a file path (because the import is already in progress) we make sure that the cache
        // exists and that the cache is up to date.
        if (!$filePath) {
            if (!$filesystem->exists($cacheDirectory . '/rule-package.json')) {
                $files = $this->rulePackageHelper->organizeRulePackageFiles($processingJob->getRulePackage());
                $filePath = $files['content'];
            } else if ($processingJob->getSourceUpdatedAt()) {
                $rulePackageContent = file_get_contents($cacheDirectory . '/rule-package.json');
                $data = json_decode($rulePackageContent);
                $updatedAt = new DateTime($data->lastUpdatedAt);

                // Download the rule package again because the cache was old.
                if ($updatedAt != $processingJob->getSourceUpdatedAt()) {
                    $files = $this->rulePackageHelper->organizeRulePackageFiles($processingJob->getRulePackage());
                    $filePath = $files['content'];
                }
            }
        }

        if ($filesystem->exists($filePath)) {
            if ($filesystem->exists($cacheDirectory . '/rule-package.json')) {
                $filesystem->remove(new \FilesystemIterator($cacheDirectory . '/'));
            }

            $zip = new \ZipArchive();
            $zip->open($filePath);

            $zip->extractTo($cacheDirectory);
            $zip->close();
            unset($zip);
        }

        if (!$filesystem->exists($cacheDirectory . '/rule-package.json')) {
            if ($this->logger instanceof Logger) {
                $this->logger->popHandler();
            }

            throw new Exception('Rule package file does not exist. Was the rule package extracted correctly?');
        }

        $this->mayInitializeProcessedData($processingJob);

        // Check if we already processed the rule package file
        if (!($processingJob->getProcessedImportData()['rulePackage'] ?? false)) {
            $rulePackageContent = file_get_contents($cacheDirectory . '/rule-package.json');
            if (!$rulePackageContent) {
                if ($this->logger instanceof Logger) {
                    $this->logger->popHandler();
                }

                throw new Exception('Rule package zip file does not contain a rule-package.json file.');
            }

            $data = json_decode($rulePackageContent);
            unset($rulePackageContent);

            // Validate rule package content
            $validator = $this->initializeValidator();
            $validationResult = $validator->validate($data, 'http://schema.mosparo.io/zipped-rule-package.json');
            if (!$validationResult->isValid()) {
                throw new Exception('Rule package file is not valid.');
            }

            $updatedAt = new DateTime($data->lastUpdatedAt);

            $rulePackageCache = $this->processRulePackageContent($processingJob, $data, $updatedAt);

            // Abort if we did not get a cache back - this means the rule package cache is already up to date.
            if (!$rulePackageCache) {
                if ($this->logger instanceof Logger) {
                    $this->logger->popHandler();
                }

                return RulePackageResult::ALREADY_UP_TO_DATE;
            }

            $processingJob->setProcessedImportDataWithKey('rulePackage', true);
            $processingJob->setImportTasks(count($data->rFiles) + count($data->riFiles));

            if ($this->maxSeconds && $this->maxSeconds < (time() - $this->startTime)) {
                if ($this->logger instanceof Logger) {
                    $this->logger->popHandler();
                }

                $this->entityManager->flush();
                return RulePackageResult::UNFINISHED;
            }
        } else {
            $rulePackageContent = file_get_contents($cacheDirectory . '/rule-package.json');
            $data = json_decode($rulePackageContent);

            $rulePackageCache = $processingJob->getRulePackage()->getRulePackageCache();
        }

        // Loop through the rules
        foreach ($data->rFiles as $rFile) {
            if (!in_array($rFile, $processingJob->getProcessedImportData()['rFiles'])) {
                $this->processRuleFile($processingJob, $rulePackageCache, $cacheDirectory, $rFile);
                $processingJob
                    ->addProcessedImportDataWithKey('rFiles', $rFile)
                    ->increaseProcessedImportTasks()
                ;
            }

            if ($this->maxSeconds && $this->maxSeconds < (time() - $this->startTime)) {
                if ($this->logger instanceof Logger) {
                    $this->logger->popHandler();
                }

                $this->entityManager->flush();
                return RulePackageResult::UNFINISHED;
            }
        }

        // Loop through the rule item files
        foreach ($data->riFiles as $riFile) {
            if (!in_array($riFile, $processingJob->getProcessedImportData()['riFiles'])) {
                $this->processRuleItemFile($processingJob, $rulePackageCache, $cacheDirectory, $riFile, $batchSize);
                $processingJob
                    ->addProcessedImportDataWithKey('riFiles', $riFile)
                    ->increaseProcessedImportTasks()
                ;
            }

            if ($this->maxSeconds && $this->maxSeconds < (time() - $this->startTime)) {
                if ($this->logger instanceof Logger) {
                    $this->logger->popHandler();
                }

                $this->entityManager->flush();
                return RulePackageResult::UNFINISHED;
            }
        }

        // Add the middlewares again, just in case
        if ($middlewares) {
            $this->entityManager->getConnection()->getConfiguration()->setMiddlewares($middlewares);
        }

        if ($this->logger instanceof Logger) {
            $this->logger->popHandler();
        }

        return RulePackageResult::COMPLETED;
    }

    protected function processRulePackageContent(RulePackageProcessingJob $processingJob, \stdClass $data, DateTime $updatedAt): ?RulePackageCache
    {
        $rulePackageCache = $processingJob->getRulePackage()->getRulePackageCache();

        if ($rulePackageCache === null) {
            $rulePackageCache = new RulePackageCache();
            $rulePackageCache->setRulePackage($processingJob->getRulePackage());
            $rulePackageCache->setProject($processingJob->getRulePackage()->getProject());
            $processingJob->getRulePackage()->setRulePackageCache($rulePackageCache);

            $this->entityManager->persist($rulePackageCache);
        }

        if ($rulePackageCache->getRefreshInterval() != $data->refreshInterval) {
            $rulePackageCache->setRefreshInterval($data->refreshInterval);
        }

        $rulePackageCache->setRefreshedAt(new DateTime());

        // We do nothing if the rulePackage wasn't updated
        if ($updatedAt == $rulePackageCache->getUpdatedAt()) {
            return null;
        }

        // Adjust the timezone from the rule package content to the one configured on this server
        if ($updatedAt->getTimezone() !== $rulePackageCache->getRefreshedAt()->getTimezone()) {
            $updatedAt->setTimezone($rulePackageCache->getRefreshedAt()->getTimezone());
        }

        $rulePackageCache->setUpdatedAt($updatedAt);
        $processingJob->setSourceUpdatedAt($updatedAt);

        $this->entityManager->flush();

        return $rulePackageCache;
    }

    protected function processRuleFile(RulePackageProcessingJob $processingJob, RulePackageCache $rulePackageCache, string $tmpDir, string $fileName)
    {
        $fileContent = file_get_contents($tmpDir . '/' . $fileName);
        if (!$fileContent) {
            throw new Exception(sprintf('Rule package zip file does not contain a file with name "%s".', $fileName));
        }

        $data = json_decode($fileContent);
        unset($fileContent);

        // Validate the rules file content
        $validator = $this->initializeValidator();
        $validationResult = $validator->validate($data, 'http://schema.mosparo.io/splitted-rules-file.json');
        if (!$validationResult->isValid()) {
            throw new Exception(sprintf('Rule file "%s" is not valid.', $fileName));
        }

        // Loop through the rules
        foreach ($data as $rule) {
            $rulePackageRuleCache = $this->findRuleCache($rulePackageCache, $rule->uuid);
            if ($rulePackageRuleCache === null) {
                $rulePackageRuleCache = new RulePackageRuleCache();
                $rulePackageRuleCache->setRulePackageCache($rulePackageCache);
                $rulePackageRuleCache->setProject($processingJob->getRulePackage()->getProject());
                $rulePackageRuleCache->setUuid($rule->uuid);

                $this->entityManager->persist($rulePackageRuleCache);
            }

            $rulePackageRuleCache->setName($rule->name);
            $rulePackageRuleCache->setDescription($rule->description ?? '');
            $rulePackageRuleCache->setType($rule->type);
            $rulePackageRuleCache->setNumberOfItems($rule->numberOfItems ?? null);
            $rulePackageRuleCache->setUpdatedAt($rulePackageCache->getUpdatedAt());

            $rating = null;
            if ($rule->spamRatingFactor) {
                $rating = (float) $rule->spamRatingFactor;
            }
            $rulePackageRuleCache->setSpamRatingFactor($rating);
        }

        $this->entityManager->flush();
    }

    protected function processRuleItemFile(RulePackageProcessingJob $processingJob, RulePackageCache $rulePackageCache, string $tmpDir, string $fileName, int $batchSize)
    {
        $fileContent = file_get_contents($tmpDir . '/' . $fileName);
        if (!$fileContent) {
            throw new Exception(sprintf('Rule package zip file does not contain a file with name "%s".', $fileName));
        }

        $data = json_decode($fileContent);
        unset($fileContent);

        // Validate rule package content
        $validator = $this->initializeValidator();
        $validationResult = $validator->validate($data, 'http://schema.mosparo.io/splitted-rule-items-file.json');
        if (!$validationResult->isValid()) {
            throw new Exception(sprintf('Rule item file "%s" is not valid.', $fileName));
        }

        $items = [];

        $ruleUuids = [];
        $ruleItemUuids = [];

        // Find the rule and rule item UUIDs
        foreach ($data as $item) {
            if (!in_array($item->ruleUuid, $ruleUuids)) {
                $ruleUuids[] = $item->ruleUuid;
            }

            $ruleItemUuids[] = $item->uuid;
        }

        $ruleObjects = $this->findRuleObjects($rulePackageCache, $ruleUuids);
        $itemObjects = $this->findRuleItemObjects($ruleObjects, $ruleItemUuids);

        $counter = 0;

        // Process the items
        foreach ($data as $item) {
            $rulePackageRuleCache = $ruleObjects[$item->ruleUuid] ?? null;
            if (!$rulePackageRuleCache) {
                continue;
            }

            $rulePackageRuleItemCache = $itemObjects[$item->uuid] ?? null;
            if ($rulePackageRuleItemCache === null) {
                $rulePackageRuleItemCache = new RulePackageRuleItemCache();
                $rulePackageRuleItemCache->setRulePackageRuleCache($rulePackageRuleCache);
                $rulePackageRuleItemCache->setProject($rulePackageRuleCache->getProject());
                $rulePackageRuleItemCache->setUuid($item->uuid);

                $this->entityManager->persist($rulePackageRuleItemCache);
            }

            $rulePackageRuleItemCache->setType($item->type);
            $rulePackageRuleItemCache->setValue($item->value);
            $rulePackageRuleItemCache->setUpdatedAt($rulePackageRuleCache->getUpdatedAt());

            $rating = null;
            if ($item->rating) {
                $rating = (float) $item->rating;
            }
            $rulePackageRuleItemCache->setSpamRatingFactor($rating);

            $items[] = $rulePackageRuleItemCache;

            if (($counter % $batchSize) === 0) {
                $this->entityManager->flush();
                foreach ($items as $i) {
                    $this->entityManager->detach($i);
                }
                $items = [];
            }

            $counter++;
        }

        $this->entityManager->flush();

        // Cleanup
        $this->rulePackageRuleCache = [];
        foreach ($items as $item) {
            $this->entityManager->detach($item);
        }
        unset($data);
        unset($items);
        unset($ruleObjects);
        unset($itemObjects);
    }

    protected function findRuleCache(RulePackageCache $rulePackageCache, string $uuid): ?RulePackageRuleCache
    {
        if (isset($this->rulePackageRuleCache[$uuid])) {
            return $this->rulePackageRuleCache[$uuid];
        }

        $rulePackageRuleCache = $this->entityManager->getRepository(RulePackageRuleCache::class)->findOneBy([
            'rulePackageCache' => $rulePackageCache,
            'uuid' => $uuid,
        ]);

        // We add all results to the cache, also a `null` result in case the cache was not found.
        $this->rulePackageRuleCache[$uuid] = $rulePackageRuleCache;

        return $rulePackageRuleCache;
    }

    protected function findRuleObjects(RulePackageCache $rulePackageCache, array $uuids): array
    {
        $qb = $this->entityManager->createQueryBuilder()
            ->select('rprc')
            ->from(RulePackageRuleCache::class, 'rprc')
            ->where('rprc.rulePackageCache = :rpc')
            ->andWhere('rprc.uuid IN (:uuids)')
            ->setParameter('rpc', $rulePackageCache)
            ->setParameter('uuids', $uuids, ArrayParameterType::STRING)
        ;

        $ruleObjects = [];
        foreach ($qb->getQuery()->getResult() as $rprc) {
            $ruleObjects[$rprc->getUuid()] = $rprc;
        }

        return $ruleObjects;
    }

    protected function findRuleItemObjects(array $ruleObjects, array $uuids): array
    {
        $ids = array_values(array_map(function ($obj) { return $obj->getId(); }, $ruleObjects));
        $qb = $this->entityManager->createQueryBuilder()
            ->select('rpric')
            ->from(RulePackageRuleItemCache::class, 'rpric')
            ->where('rpric.rulePackageRuleCache IN (:rprc)')
            ->andWhere('rpric.uuid IN (:uuids)')
            ->setParameter('rprc', $ids, ArrayParameterType::INTEGER)
            ->setParameter('uuids', $uuids, ArrayParameterType::STRING)
        ;

        $ruleItemObjects = [];
        foreach ($qb->getQuery()->getResult() as $rprc) {
            $ruleItemObjects[$rprc->getUuid()] = $rprc;
        }

        return $ruleItemObjects;
    }

    protected function initializeValidator(): Validator
    {
        $validator = new Validator();
        $validator->resolver()->registerFile('http://schema.mosparo.io/zipped-rule-package.json', Specifications::getJsonSchemaPath(Specifications::JSON_SCHEMA_ZIPPED_RULE_PACKAGE));
        $validator->resolver()->registerFile('http://schema.mosparo.io/splitted-rules-file.json', Specifications::getJsonSchemaPath(Specifications::JSON_SCHEMA_SPLITTED_RULES_FILE));
        $validator->resolver()->registerFile('http://schema.mosparo.io/splitted-rule-items-file.json', Specifications::getJsonSchemaPath(Specifications::JSON_SCHEMA_SPLITTED_RULE_ITEMS_FILE));

        return $validator;
    }

    protected function mayInitializeProcessedData(RulePackageProcessingJob $processingJob): void
    {
        if ($processingJob->getProcessedImportData()) {
            return;
        }

        $processingJob->setProcessedImportData([
            'rulePackage' => false,
            'rFiles' => [],
            'riFiles' => [],
        ]);
    }
}
