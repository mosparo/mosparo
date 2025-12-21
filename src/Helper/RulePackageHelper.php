<?php

namespace Mosparo\Helper;

use DateInterval;
use DateTime;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\ORM\EntityManagerInterface;
use Mosparo\Entity\Project;
use Mosparo\Entity\RulePackageCache;
use Mosparo\Entity\RulePackageRuleCache;
use Mosparo\Entity\RulePackageRuleItemCache;
use Mosparo\Entity\RulePackageProcessingJob;
use Mosparo\Enum\ProcessingJobType;
use Mosparo\Enum\RulePackageResult;
use Mosparo\Enum\RulePackageType;
use Mosparo\Exception;
use Mosparo\Entity\RulePackage;
use Mosparo\RulePackage\Importer\JsonImporter;
use Mosparo\RulePackage\Importer\ZipImporter;
use Mosparo\RulePackage\ImporterInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Filesystem\Filesystem;
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

    protected LoggerInterface $logger;

    protected string $rulePackageDirectory;

    protected ImporterInterface $importer;

    public function __construct(
        EntityManagerInterface $entityManager,
        UrlGeneratorInterface $router,
        HttpClientInterface $client,
        ConnectionHelper $connectionHelper,
        CleanupHelper $cleanupHelper,
        ProjectHelper $projectHelper,
        LoggerInterface $logger,
        string $rulePackageDirectory
    ) {
        $this->entityManager = $entityManager;
        $this->router = $router;
        $this->client = $client;
        $this->connectionHelper = $connectionHelper;
        $this->cleanupHelper = $cleanupHelper;
        $this->projectHelper = $projectHelper;
        $this->logger = $logger;
        $this->rulePackageDirectory = $rulePackageDirectory;
    }

    public function hasRulePackages(?Project $project = null): bool
    {
        if ($project === null) {
            $project = $this->projectHelper->getActiveProject();
        }

        return $this->entityManager->getRepository(RulePackage::class)->findOneBy(['project' => $project]) !== null;
    }

    public function fetchAll(?int $startTime = null, ?int $maxSeconds = null): void
    {
        $projectRepository = $this->entityManager->getRepository(Project::class);
        $rulePackageRepository = $this->entityManager->getRepository(RulePackage::class);

        foreach ($projectRepository->findAll() as $project) {
            $this->projectHelper->setActiveProject($project);

            foreach ($rulePackageRepository->findBy(['status' => 1]) as $rulePackage) {
                if (in_array($rulePackage->getType(), RulePackageType::automaticTypes())) {
                    $this->fetchRulePackage($rulePackage, $startTime, $maxSeconds);
                }
            }

            $this->entityManager->flush();
        }
    }

    public function fetchRulePackages(array $rulePackages, ?int $startTime = null, ?int $maxSeconds = null): void
    {
        foreach ($rulePackages as $rulePackage) {
            if ($rulePackage->isActive() && in_array($rulePackage->getType(), RulePackageType::automaticTypes())) {
                $this->fetchRulePackage($rulePackage, $startTime, $maxSeconds);
            }
        }

        $this->entityManager->flush();
    }

    public function fetchRulePackage(RulePackage $rulePackage, ?int $startTime = null, ?int $maxSeconds = null): RulePackageResult
    {
        if (!$rulePackage->isActive() || !in_array($rulePackage->getType(), RulePackageType::automaticTypes())) {
            return RulePackageResult::NOT_AN_AUTOMATIC_TYPE;
        }

        $isInProgress = $this->isInProgress($rulePackage);
        $rulePackageCache = $rulePackage->getRulePackageCache();
        if ($rulePackageCache !== null && $rulePackageCache->getRefreshedAt() !== null) {
            $refreshInterval = new DateInterval('PT' . $rulePackageCache->getRefreshInterval() . 'S');
            $timeLeast = (clone $rulePackageCache->getRefreshedAt())->add($refreshInterval);

            // We're not allowed to download the rule package again.
            if ($timeLeast > new DateTime() && !$isInProgress) {
                return RulePackageResult::TOO_SOON_AFTER_THE_LAST_UPDATE;
            }
        }

        $files = [];
        if (!$isInProgress) {
            $files = $this->organizeRulePackageFiles($rulePackage);
        }

        $result = $this->validateAndProcessContent($rulePackage, $files['content'] ?? null, $isInProgress, $startTime, $maxSeconds);

        if (in_array($result, [RulePackageResult::COMPLETED, RulePackageResult::ALREADY_UP_TO_DATE, RulePackageResult::NOT_AN_AUTOMATIC_TYPE, RulePackageResult::TOO_SOON_AFTER_THE_LAST_UPDATE])) {
            $this->deleteCacheDirectory($rulePackage->getId());

            $processingJob = $rulePackage->getFirstProcessingJob(ProcessingJobType::UPDATE_CACHE);
            if ($processingJob) {
                $rulePackage->getProcessingJobs()->removeElement($processingJob);
                $this->entityManager->remove($processingJob);
                $this->entityManager->flush();
            }
        }

        return $result;
    }

    public function organizeRulePackageFiles(RulePackage $rulePackage): array
    {
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

        if (hash_file('sha256', $files['content']) !== $fileHash) {
            throw new Exception('Hash verification failed.');
        }

        return $files;
    }

    public function getCacheDirectory(int $rulePackageId): string
    {
        return $this->rulePackageDirectory . '/' . $rulePackageId;
    }

    public function deleteCacheDirectory(int $rulePackageId)
    {
        $cacheDir = $this->getCacheDirectory($rulePackageId);
        $filesystem = new Filesystem();
        if ($filesystem->exists($cacheDir)) {
            $filesystem->remove($cacheDir);
        }
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

            if ($fileType === 'content') {
                $tmpFilePath = tempnam(sys_get_temp_dir(), 'rp');
                file_put_contents($tmpFilePath, $response->getContent());

                $files[$fileType] = $tmpFilePath;
            } else {
                $files[$fileType] = $response->getContent();
            }
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

            if ($fileType === 'content') {
                $files[$fileType] = $filePath;
            } else {
                $files[$fileType] = file_get_contents($filePath);
            }
        }

        return $files;
    }

    protected function verifyUrl($url): void
    {
        $path = parse_url($url, PHP_URL_PATH);

        if (empty($path)) {
            throw new Exception(sprintf('The URL to a rule package needs to be fully qualified. There is no path in the URL "%s".', $url));
        }
    }

    public function validateAndProcessContent(RulePackage $rulePackage, ?string $filePath, bool $isInProgress, ?int $startTime = null, ?int $maxSeconds = null): RulePackageResult
    {
        // Prepare the cache directory
        $cacheDir = $this->getCacheDirectory($rulePackage->getId());
        $filesystem = new Filesystem();
        if (!$filesystem->exists($cacheDir)) {
            $filesystem->mkdir($cacheDir);
        }

        // Get the mime type to determine the importer
        $processingJob = $rulePackage->getFirstProcessingJob(ProcessingJobType::UPDATE_CACHE);
        if (!$processingJob && $filePath !== null) {
            $this->logger->debug('Create RulePackageProcessingJob to update the rule package cache.');
            $processingJob = (new RulePackageProcessingJob())
                ->setRulePackage($rulePackage)
                ->setType(ProcessingJobType::UPDATE_CACHE)
                ->setProject($rulePackage->getProject())
                ->setMimetype(mime_content_type($filePath))
            ;

            $this->entityManager->persist($processingJob);
        } else if ($isInProgress) {
            $this->logger->debug('Continue the previously added RulePackageProcessingJob to update the rule package cache.');

            if ($isInProgress) {
                $filePath = '';
            }
        }

        if (!$processingJob) {
            throw new Exception('No processing job available. Abort.');
        }

        // Prepare the importer
        try {
            $this->importer = match($processingJob->getMimetype()) {
                'application/json' => new JsonImporter($this->entityManager),
                'application/zip' => new ZipImporter($this, $this->entityManager, $this->logger, $this->rulePackageDirectory, $startTime, $maxSeconds)
            };
        } catch (\UnhandledMatchError $e) {
            $this->logger->error('Unknown rule package file type. Cannot find importer.');
            throw new Exception('Unknown rule package file type.');
        }

        // Do the work
        $this->logger->info('Start importing the rule package.');
        $result = $this->importer->importRulePackage($processingJob, $filePath, $cacheDir);

        if ($result !== RulePackageResult::COMPLETED) {
            $this->logger->info('Stopped importing the rule package.');
            return $result;
        }

        $this->logger->info('Finished importing the rule package.');

        // Cleanup
        return $this->cleanupForRulePackage($processingJob, $cacheDir, true, $startTime, $maxSeconds);
    }

    public function cleanupForRulePackage(RulePackageProcessingJob $processingJob, string $cacheDir, bool $outdatedOnly = false, $startTime = null, $maxSeconds = null): RulePackageResult
    {
        // Count the outdated rule items
        if ($processingJob->getCleanupTasks() === 0) {
            if ($outdatedOnly) {
                $processingJob->setCleanupTasks($this->countNumberOfOutdatedRuleItems($processingJob->getRulePackage()->getRulePackageCache()));
            } else {
                $processingJob->setCleanupTasks($this->countNumberOfRuleItems($processingJob->getRulePackage()->getRulePackageCache()));
            }
        }

        // Remove outdated rules
        if ($processingJob->getProcessedCleanupDataWithKey('cleanedOutdatedRules') === null) {
            $result = $this->removeRules($processingJob, $outdatedOnly, $startTime, $maxSeconds);

            if ($result !== RulePackageResult::COMPLETED) {
                return RulePackageResult::UNFINISHED;
            } else {
                $processingJob->setProcessedCleanupDataWithKey('cleanedOutdatedRules', true);
            }
        }

        // Remove outdated rule items
        $result = $this->removeRuleItems($processingJob, $outdatedOnly, $startTime, $maxSeconds);

        // Delete the cache directory if the result is not unfinished
        if ($result !== RulePackageResult::UNFINISHED) {
            $filesystem = new Filesystem();
            $filesystem->remove($cacheDir);

            $this->entityManager->remove($processingJob);
        }

        return $result;
    }

    protected function isInProgress(RulePackage $rulePackage): bool
    {
        $processingJob = $rulePackage->getProcessingJobs()->first();
        if (!$processingJob) {
            return false;
        }

        $continuableMimetypes = ['application/zip'];
        $mimetype = $processingJob->getMimetype();
        if (!$mimetype || !in_array($mimetype, $continuableMimetypes)) {
            return false;
        }

        return true;
    }

    public function countNumberOfRuleItems($rulePackageCache): int
    {
        $qb = $this->entityManager->createQueryBuilder()
            ->select('rprc.id')
            ->from(RulePackageRuleCache::class, 'rprc')
            ->where('rprc.rulePackageCache = :rulePackageCache')
            ->setParameter('rulePackageCache', $rulePackageCache)
        ;
        $rulePackageRuleCacheIds = $qb->getQuery()->getSingleColumnResult();

        $qb = $this->entityManager->createQueryBuilder();
        $qb
            ->select('COUNT(rpric.id)')
            ->distinct()
            ->from(RulePackageRuleItemCache::class, 'rpric')
            ->where('rpric.rulePackageRuleCache IN (:rules)')
            ->setParameter('rules', $rulePackageRuleCacheIds, ArrayParameterType::INTEGER)
        ;

        return $qb->getQuery()->getSingleScalarResult();
    }

    public function countNumberOfOutdatedRuleItems(RulePackageCache $rulePackageCache): int
    {
        $qb = $this->entityManager->createQueryBuilder()
            ->select('rprc.id')
            ->from(RulePackageRuleCache::class, 'rprc')
            ->where('rprc.rulePackageCache = :rulePackageCache')
            ->andWhere('rprc.updatedAt = :updatedAt')
            ->setParameter('rulePackageCache', $rulePackageCache)
            ->setParameter('updatedAt', $rulePackageCache->getUpdatedAt())
        ;
        $rulePackageRuleCacheIds = $qb->getQuery()->getSingleColumnResult();

        $expr = $qb->expr()->orX()
            ->add('rprc.updatedAt IS NULL')
            ->add('rprc.updatedAt < :updatedAt')
        ;
        $qb = $this->entityManager->createQueryBuilder()
            ->select('rprc.id')
            ->from(RulePackageRuleCache::class, 'rprc')
            ->where('rprc.rulePackageCache = :rulePackageCache')
            ->andWhere($expr)
            ->setParameter('rulePackageCache', $rulePackageCache)
            ->setParameter('updatedAt', $rulePackageCache->getUpdatedAt())
        ;
        $expiredRulePackageRuleCacheIds = $qb->getQuery()->getSingleColumnResult();

        $qb = $this->entityManager->createQueryBuilder();
        $expr = $qb->expr()->andX()
            ->add('rpric.rulePackageRuleCache IN (:rules)')
            ->add('rpric.updatedAt < :updatedAt')
        ;

        $qb
            ->select('COUNT(rpric.id)')
            ->distinct()
            ->from(RulePackageRuleItemCache::class, 'rpric')
            ->where('rpric.rulePackageRuleCache IN (:expiredRules)')
            ->orWhere($expr)
            ->setParameter('expiredRules', $expiredRulePackageRuleCacheIds, ArrayParameterType::INTEGER)
            ->setParameter('rules', $rulePackageRuleCacheIds, ArrayParameterType::INTEGER)
            ->setParameter('updatedAt', $rulePackageCache->getUpdatedAt())
        ;

        return $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * Removes the outdated rule caches from the given rule package cache.
     *
     * @param RulePackageProcessingJob $processingJob
     * @return RulePackageResult
     */
    public function removeRules(RulePackageProcessingJob $processingJob, bool $outdatedOnly = false, ?float $startTime = null, ?int $maxSeconds = null): RulePackageResult
    {
        $qb = $this->entityManager->createQueryBuilder()
            ->select('rprc')
            ->from(RulePackageRuleCache::class, 'rprc')
            ->where('rprc.rulePackageCache = :rpc')
            ->setParameter('rpc', $processingJob->getRulePackage()->getRulePackageCache())
        ;

        if ($outdatedOnly) {
            $qb
                ->andWhere('rprc.updatedAt IS NULL OR rprc.updatedAt < :updatedAt')
                ->setParameter('updatedAt', $processingJob->getRulePackage()->getRulePackageCache()->getUpdatedAt())
            ;
        }

        foreach ($qb->getQuery()->getResult() as $rprc) {
            // Here we delete all items for the given rule, so outdatedOnly is false
            $result = $this->removeItemsForRule($processingJob, $rprc, false, $startTime, $maxSeconds);

            if ($result !== RulePackageResult::COMPLETED) {
                $this->entityManager->flush();

                return $result;
            }

            unset($ids);
            unset($q);
            unset($qb);

            $this->entityManager->remove($rprc);
            $this->entityManager->flush();
        }

        return RulePackageResult::COMPLETED;
    }

    /**
     * Removes the oudated rule items for the rules in the given rule package cache.
     *
     * @param RulePackageProcessingJob $processingJob
     * @param bool $outdatedOnly
     * @param null|float $startTime
     * @param null|int $maxSeconds
     * @return RulePackageResult
     */
    public function removeRuleItems(RulePackageProcessingJob $processingJob, bool $outdatedOnly = false, ?float $startTime = null, ?int $maxSeconds = null): RulePackageResult
    {
        $qb = $this->entityManager->createQueryBuilder()
            ->select('rprc')
            ->from(RulePackageRuleCache::class, 'rprc')
            ->where('rprc.rulePackageCache = :rpc')
            ->andWhere('rprc.id NOT IN (:ids)')
            ->setParameter('rpc', $processingJob->getRulePackage()->getRulePackageCache())
            ->setParameter('ids', $processingJob->getProcessedCleanupDataWithKey('ruleCaches') ?? [], ArrayParameterType::INTEGER)
        ;

        $maxRprc = 10;
        $countQb = (clone $qb)->select('COUNT(rprc.id)');
        $rprcCounter = $countQb->getQuery()->getSingleScalarResult();
        $numberOfIterations = ceil($rprcCounter / $maxRprc);

        for ($counter = 0; $counter < $numberOfIterations; $counter++) {
            $qb->setMaxResults(10);

            foreach ($qb->getQuery()->getResult() as $rprc) {
                $this->removeItemsForRule($processingJob, $rprc, $outdatedOnly, $startTime, $maxSeconds);

                $processingJob->addProcessedCleanupDataWithKey('ruleCaches', $rprc->getId());
            }

            if ($maxSeconds && $maxSeconds < (time() - $startTime)) {
                return RulePackageResult::UNFINISHED;
            }
        }

        return RulePackageResult::COMPLETED;
    }

    /**
     * Deletes the items for the given rule package rule cache, either all or only the oudates ones.
     *
     * @param RulePackageProcessingJob $processingJob
     * @param RulePackageRuleCache $rprc
     * @param bool $outdatedOnly
     * @param null|float $startTime
     * @param null|int $maxSeconds
     * @return RulePackageResult
     */
    protected function removeItemsForRule(RulePackageProcessingJob $processingJob, RulePackageRuleCache $rprc, bool $outdatedOnly = false, ?float $startTime = null, ?int $maxSeconds = null): RulePackageResult
    {
        $qb = $this->entityManager->createQueryBuilder()
            ->select('rpric.id')
            ->from(RulePackageRuleItemCache::class, 'rpric')
            ->where('rpric.rulePackageRuleCache = :rprc')
            ->setParameter('rprc', $rprc)
            ->setMaxResults(10000)
        ;

        if ($outdatedOnly) {
            $qb
                ->andWhere('rpric.updatedAt < :updatedAt')
                ->setParameter('updatedAt', $rprc->getUpdatedAt())
            ;
        }

        $query = $qb->getQuery();

        $numberOfDeleted = null;
        while ($numberOfDeleted === null || $numberOfDeleted > 0) {
            $ids = $query->getSingleColumnResult();
            if (!$ids) {
                break;
            }

            $delQb = $this->entityManager->createQueryBuilder()
                ->delete()
                ->from(RulePackageRuleItemCache::class, 'rpric')
                ->where('rpric.id IN (:ids)')
                ->setParameter('ids', $ids, ArrayParameterType::INTEGER)
            ;
            $numberOfDeleted = $delQb->getQuery()->execute();
            $processingJob->increaseProcessedCleanupTasks($numberOfDeleted);

            if ($maxSeconds && $maxSeconds < (time() - $startTime)) {
                return RulePackageResult::UNFINISHED;
            }
        }

        return RulePackageResult::COMPLETED;
    }
}