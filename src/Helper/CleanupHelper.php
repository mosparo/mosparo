<?php

namespace Mosparo\Helper;

use DateInterval;
use DateTime;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\ORM\EntityManagerInterface;
use Mosparo\Entity\CleanupStatistic;
use Mosparo\Entity\Project;
use Mosparo\Enum\CleanupExecutor;
use Mosparo\Enum\CleanupResult;
use Mosparo\Enum\CleanupStatus;
use Mosparo\Util\DateRangeUtil;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Cache\CacheInterface;

class CleanupHelper
{
    protected EntityManagerInterface $entityManager;

    protected ProjectHelper $projectHelper;

    protected LoggerInterface $logger;

    protected CacheInterface $cache;

    protected bool $cleanupGracePeriodEnabled;

    protected DateInterval $submitTokenRetentionPeriod;

    protected DateInterval $submissionRetentionPeriod;

    protected DateInterval $cleanupProcessInterval;

    protected DateInterval $cleanupUnfinishedInterval;

    public function __construct(
        EntityManagerInterface $entityManager,
        ProjectHelper $projectHelper,
        LoggerInterface $logger,
        CacheInterface $cache,
        bool $cleanupGracePeriodEnabled = false,
        int $submitTokenRetentionPeriod = 24,
        int $submissionRetentionPeriod = 14,
        int $cleanupProcessInterval = 6,
        int $cleanupUnfinishedInterval = 10
    ) {
        $this->entityManager = $entityManager;
        $this->projectHelper = $projectHelper;
        $this->logger = $logger;
        $this->cache = $cache;
        $this->cleanupGracePeriodEnabled = $cleanupGracePeriodEnabled;

        $this->submitTokenRetentionPeriod = new DateInterval(sprintf('PT%dH', ($submitTokenRetentionPeriod >= 1 && $submitTokenRetentionPeriod <= 24) ? $submitTokenRetentionPeriod : 24)); // Hours
        $this->submissionRetentionPeriod = new DateInterval(sprintf('P%dD', ($submissionRetentionPeriod >= 1 && $submissionRetentionPeriod <= 14) ? $submissionRetentionPeriod : 14)); // Days
        $this->cleanupProcessInterval = new DateInterval(sprintf('PT%dH', ($cleanupProcessInterval >= 1 && $cleanupProcessInterval <= 24) ? $cleanupProcessInterval : 6)); // Hours
        $this->cleanupUnfinishedInterval = new DateInterval(sprintf('PT%dM', ($cleanupUnfinishedInterval >= 1 && $cleanupUnfinishedInterval <= 360) ? $cleanupUnfinishedInterval : 10)); // Minutes
    }

    public function cleanup(
        int $maxIterations = 10,
        bool $ignoreSchedule = false,
        bool $ignoreExceptions = true,
        float $timeout = 1.5,
        CleanupExecutor $cleanupExecutor = CleanupExecutor::UNKNOWN
    ): CleanupResult {
        $nextCleanup = $this->cache->getItem('mosparoNextCleanup');
        $additionalCleanup = $this->cache->getItem('mosparoAdditionalCleanup');
        $cleanupStartedAt = $this->cache->getItem('mosparoCleanupStartedAt');

        // If the ignoreSchedule argument is set, we execute the cleanup immediately.
        if (!$ignoreSchedule) {
            // Clone the DateTime object to keep the original time because we manipulate the time later (see below).
            if ($nextCleanup->get() !== null) {
                $cleanupStart = clone $nextCleanup->get();
            } else {
                $cleanupStart = $this->getLastDatabaseCleanup();
                if (!$cleanupStart) {
                    $cleanupStart = new DateTime();
                }

                $cleanupStart->add($this->cleanupProcessInterval);
            }

            // Add the cleanup grace period - if enabled - to the regular cleanup time but not the
            // additional cleanup (see below).
            if ($this->cleanupGracePeriodEnabled) {
                $cleanupStart->add($this->submitTokenRetentionPeriod);
            }

            // Check if there is an additional cleanup needed from the last cleanup run. It will override all
            // the other times.
            if ($additionalCleanup->get() !== null) {
                $cleanupStart = $additionalCleanup->get();
            }

            // Return, if the next cleanup date is in the future
            if ($cleanupStart > new DateTime()) {
                return CleanupResult::NEXT_CLEANUP_IN_THE_FUTURE;
            }

            // Do not start the cleanup if another request is already executing the cleanup
            if ($cleanupStartedAt->get() !== null && $cleanupStartedAt->get() > (new DateTime())->sub(new DateInterval('PT5M'))) {
                return CleanupResult::CLEANUP_RUNNING_ALREADY;
            }
        }

        // Log the start of the cleanup process
        $this->logger->info(sprintf(
            'Start cleanup process (Max iterations: %d; Ignore schedule: %b; Ignore exceptions: %b, Timeout: %01.1fs)',
            $maxIterations,
            $ignoreSchedule,
            $ignoreExceptions,
            $timeout
        ));

        // Sleep for a little bit of a second to make sure that other requests are not trying to clean the database
        // as well. This affects the CLI Command (which could be executed multiple times by multiple cron jobs), as well
        // as the frontend controller (which could be executed multiple times when the web server receives multiple
        // requests in the same moment).
        usleep(mt_rand(1000, 100000));

        // Try to determine if another cleanup process is already running (mostly to protect multi-node setups from executing the
        // cleanup logic in different processes on different nodes). This one is different from the check below, because
        // this one only counts cleanup statistics that have the status 'unknown', while the check below checks for any
        // cleanup statistic in the last minutes.
        if ($this->hasUnfinshedCleanupStatistics()) {
            return CleanupResult::UNFINISHED_CLEANUP_STATISTIC_OBJECT;
        }

        $cleanupStatistic = (new CleanupStatistic())
            ->setCleanupExecutor($cleanupExecutor);

        $maxPerIteration = 2500;
        $notFinished = true;
        $startTime = microtime(true);

        // Lock the cleanup
        $cleanupStartedAt->set(new DateTime());
        if (!$this->cache->save($cleanupStartedAt)) {
            // We execute this code in case the (shared) cache is unavailable.
            $this->logger->error('Failed to lock the cleanup process, probably because the cache is unavailable.');

            // Try to find any CleanupStatistic objects from the last 10 minutes
            $qb = $this->entityManager->createQueryBuilder()
                ->select('cs.id')
                ->from(CleanupStatistic::class, 'cs')
                ->where('cs.dateTime > :minTime')
                ->setParameter('minTime', (new DateTime())->sub($this->cleanupUnfinishedInterval))
                ->setMaxResults(1)
            ;

            if ($qb->getQuery()->getOneOrNullResult()) {
                // If the last CleanupStatistic was created less than 10 minutes ago, abort here and try it later.
                $this->logger->info('Aborting the cleanup process because the last cleanup was executed less than 10 minutes ago.');
                return CleanupResult::CLEANUP_JUST_EXECUTED;
            }
        }

        // Store the CleanupStatistic object to additionally lock the cleanup process.
        $this->entityManager->persist($cleanupStatistic);
        $this->entityManager->flush();
        $this->entityManager->refresh($cleanupStatistic);

        // Remove the active project for the cleanup
        $activeProject = null;
        if ($this->projectHelper->hasActiveProject()) {
            $activeProject = $this->projectHelper->getActiveProject();
            $this->projectHelper->unsetActiveProject();
        }

        // Generally, we ignore all exceptions which could happen from here on.
        // The part above and below this try-catch is important to make sure that
        // the project filter is active again.
        try {
            // Delete expired delays
            $qb = $this->entityManager->createQueryBuilder();
            $qb
                ->delete('Mosparo\Entity\Delay', 'd')
                ->where('d.validUntil < :now')
                ->setParameter('now', new DateTime())
                ->getQuery()->execute();
            unset($qb);

            // Delete expired lockouts
            $qb = $this->entityManager->createQueryBuilder();
            $qb
                ->delete('Mosparo\Entity\Lockout', 'l')
                ->where('l.validUntil < :now')
                ->setParameter('now', new DateTime())
                ->getQuery()->execute();
            unset($qb);

            for ($iterationCounter = 0; $iterationCounter < $maxIterations; $iterationCounter++) {
                // Find all deletable submissions and submit tokens
                $query = $this->entityManager->createQuery('
                        SELECT s.id, st.id AS stId
                        FROM Mosparo\Entity\Submission s
                        JOIN s.submitToken st
                        WHERE (s.submittedAt < :limit OR (s.submittedAt < :limitDay AND s.spam = FALSE AND s.valid IS NULL))
                    ')
                    ->setParameter('limit', (new DateTime())->sub($this->submissionRetentionPeriod))
                    ->setParameter('limitDay', (new DateTime())->sub($this->submitTokenRetentionPeriod))
                    ->setMaxResults($maxPerIteration);

                $result = $query->getResult();
                $deletableSubmissionIds = array_column($result, 'id');
                $deletableSubmitTokenIds = array_unique(array_column($result, 'stId'));
                unset($query);
                unset($result);

                // The first step was to find the submit tokens and submissions that reached the end of the retention
                // period. We did that above. But now, we have to make sure that we do not try to delete anything that
                // we still need. For this, we search for the submit tokens that are used by submissions that are not
                // at the end of the retention period. These submit tokens cannot be deleted.
                //
                // Submit tokens that cannot be deleted yet are those that are used in multiple submissions.
                // Example: The user validates the form data and creates a submission. The first time, mosparo
                //          detects spam, which means that this submission is stored for 14 days (by default). But now,
                //          the user adjusts the form and revalidates the form data. Now, everything is good, so
                //          mosparo stores a second submission. But since the user does not really submit the form, the
                //          retention period of the second submission is 24 hours (by default).
                //          This means that the second submission is gonna be deleted before the first one, which is not
                //          acceptable, so we have to prevent that.
                $query = $this->entityManager->createQuery('
                        SELECT st.id 
                        FROM Mosparo\Entity\Submission s
                        JOIN s.submitToken st
                        WHERE st.id IN (:deletableSubmitTokenIds)
                        AND s.id NOT IN (:deletableSubmissionIds)
                    ')
                    ->setParameter('deletableSubmitTokenIds', $deletableSubmitTokenIds, ArrayParameterType::INTEGER)
                    ->setParameter('deletableSubmissionIds', $deletableSubmissionIds, ArrayParameterType::INTEGER)
                ;
                $result = array_unique($query->getSingleColumnResult());
                unset($query);
                $reallyDeletableSubmitTokenIds = array_diff($deletableSubmitTokenIds, $result);

                // After we found the submit tokens that really can be deleted, we can query for the submission IDs that
                // are ready for deletion. Only if the submit token is gonna be deleted, the submission can be deleted too.
                $query = $this->entityManager->createQuery('
                        SELECT s.id 
                        FROM Mosparo\Entity\Submission s
                        JOIN s.submitToken st
                        WHERE st.id IN (:deletableSubmitTokenIds)
                    ')
                    ->setParameter('deletableSubmitTokenIds', $reallyDeletableSubmitTokenIds, ArrayParameterType::INTEGER)
                ;
                $reallyDeletableSubmissionIds = array_unique($query->getSingleColumnResult());

                // If we have nothing left, it means that all the other submissions and submit tokens are still required.
                if (count($reallyDeletableSubmissionIds) === 0) {
                    // Break the loop when we have no more deletable submissions
                    $notFinished = false;
                    break;
                }

                // Remove the connection between submissions and submit tokens
                $query = $this->entityManager->createQuery('
                        UPDATE Mosparo\Entity\Submission s
                        SET s.submitToken = NULL
                        WHERE s.id IN (:deletableSubmissionIds)
                    ')
                    ->setParameter('deletableSubmissionIds', $reallyDeletableSubmissionIds, ArrayParameterType::INTEGER);
                $query->execute();
                unset($query);

                // Delete the submit tokens
                if ($reallyDeletableSubmitTokenIds) {
                    $query = $this->entityManager->createQuery('
                            DELETE Mosparo\Entity\SubmitToken st
                            WHERE st.id IN (:deletableSubmitTokenIds)
                        ')
                        ->setParameter('deletableSubmitTokenIds', $reallyDeletableSubmitTokenIds, ArrayParameterType::INTEGER);
                    $query->execute();
                    unset($query);
                }

                // Delete the submissions
                if ($deletableSubmissionIds) {
                    $query = $this->entityManager->createQuery('
                            DELETE Mosparo\Entity\Submission s
                            WHERE s.id IN (:deletableSubmissionIds)
                        ')
                        ->setParameter('deletableSubmissionIds', $reallyDeletableSubmissionIds, ArrayParameterType::INTEGER);
                    $query->execute();
                    unset($query);
                }

                $cleanupStatistic
                    ->increaseNumberOfDeletedSubmitTokens(count($reallyDeletableSubmitTokenIds))
                    ->increaseNumberOfDeletedSubmissions(count($reallyDeletableSubmissionIds));

                // If it took more than the allowed timeout, stop the cleanup.
                if ($timeout > 0 && $maxIterations > 1 && (microtime(true) - $startTime) > $timeout) {
                    $this->logger->info(sprintf(
                        'Cleanup process aborted after %.2fs because the timeout of %ds was reached.',
                        (microtime(true) - $startTime),
                        $timeout
                    ));
                    break;
                }
            }

            // Cleanup incomplete submissions and submit tokens
            $this->deleteSubmissionsWithoutAssignedSubmitTokens($cleanupStatistic);
            $this->deleteSubmitTokensWithoutSubmissions($cleanupStatistic);

            // Clear the day statistic
            $this->cleanupDayStatistcs();
        } catch (\Exception $e) {
            // Throw the exception if we should not ignore exceptions.
            if (!$ignoreExceptions) {
                throw $e;
            }

            $this->logger->critical($e->getMessage());

            // Since there was an error, let's try that again in 10 minutes
            $notFinished = true;
        }

        // Count the submit tokens and submissions for the statistic
        $query = $this->entityManager->createQuery('SELECT COUNT(st.id) FROM Mosparo\Entity\SubmitToken st');
        $cleanupStatistic->setNumberOfStoredSubmitTokens($query->getSingleScalarResult());
        unset($query);

        $query = $this->entityManager->createQuery('SELECT COUNT(s.id) FROM Mosparo\Entity\Submission s');
        $cleanupStatistic->setNumberOfStoredSubmissions($query->getSingleScalarResult());
        unset($query);

        // Set the active project after the cleanup
        if ($activeProject !== null) {
            $this->projectHelper->setActiveProject($activeProject);
        }

        $additionalCleanupDate = null;
        if ($notFinished) {
            // Execute the next cleanup in 10 minutes
            // We give the (database) server these 10 minutes to relax after deleting so many rows.
            $additionalCleanupDate = (new DateTime())->add($this->cleanupUnfinishedInterval);
            $cleanupStatistic->setCleanupStatus(CleanupStatus::INCOMPLETE);
        } else {
            $cleanupStatistic->setCleanupStatus(CleanupStatus::COMPLETE);
        }

        $additionalCleanup->set($additionalCleanupDate);
        $this->cache->save($additionalCleanup);

        // The next regular cleanup will be performed in (normally) 6 hours
        $nextCleanup->set((new DateTime())->add($this->cleanupProcessInterval));
        $this->cache->save($nextCleanup);

        $executionTime = microtime(true) - $startTime;
        $this->logger->info(sprintf(
            'Cleanup process completed after %.2fs.',
            $executionTime
        ));

        // Store the cleanup statistic object
        $cleanupStatistic->setExecutionTime($executionTime);
        $this->entityManager->persist($cleanupStatistic);
        $this->entityManager->flush();

        $cleanupStartedAt->set(null);
        $this->cache->save($cleanupStartedAt);

        return ($notFinished) ? CleanupResult::UNFINISHED : CleanupResult::COMPLETED;
    }

    /**
     * Cleanup the day statistics objects
     *
     * @return void
     */
    public function cleanupDayStatistcs()
    {
        $projects = $this->entityManager->getRepository(Project::class)->findAll();
        foreach ($projects as $project) {
            if ($project->getStatisticStorageLimit() === DateRangeUtil::DATE_RANGE_FOREVER) {
                continue;
            }

            $minDate = DateRangeUtil::getStartDateForRange($project->getStatisticStorageLimit());

            $qb = $this->entityManager->createQueryBuilder();
            $qb->delete('Mosparo\Entity\DayStatistic', 'ds')
                ->where('ds.date < :minDate')
                ->andWhere('ds.project = :project')
                ->setParameter('minDate', $minDate)
                ->setParameter('project', $project)
                ->getQuery()->execute();
            unset($qb);
        }

        unset($projects);
        unset($project);
    }

    /**
     * Cleanup all the project entities. This is used
     * when a project is deleted.
     *
     * @param \Mosparo\Entity\Project $project
     * @return void
     */
    public function cleanupProjectEntities(Project $project)
    {
        // Delete all rule items
        $qb = $this->entityManager->createQueryBuilder();
        $qb->delete('Mosparo\Entity\RuleItem', 'ri')
            ->where('ri.project = :project')
            ->setParameter('project', $project)
            ->getQuery()->execute();
        unset($qb);

        // Delete all rules
        $qb = $this->entityManager->createQueryBuilder();
        $qb->delete('Mosparo\Entity\Rule', 'r')
            ->where('r.project = :project')
            ->setParameter('project', $project)
            ->getQuery()->execute();
        unset($qb);

        // Delete all rule packages rule item cache
        $qb = $this->entityManager->createQueryBuilder();
        $qb->delete('Mosparo\Entity\RulePackageRuleItemCache', 'rsric')
            ->where('rsric.project = :project')
            ->setParameter('project', $project)
            ->getQuery()->execute();
        unset($qb);

        // Delete all rule packages rule cache
        $qb = $this->entityManager->createQueryBuilder();
        $qb->delete('Mosparo\Entity\RulePackageRuleCache', 'rsrc')
            ->where('rsrc.project = :project')
            ->setParameter('project', $project)
            ->getQuery()->execute();
        unset($qb);

        // Delete all rule packages cache
        $qb = $this->entityManager->createQueryBuilder();
        $qb->delete('Mosparo\Entity\RulePackageCache', 'rsc')
            ->where('rsc.project = :project')
            ->setParameter('project', $project)
            ->getQuery()->execute();
        unset($qb);

        // Delete all rule packages
        $qb = $this->entityManager->createQueryBuilder();
        $qb->delete('Mosparo\Entity\RulePackage', 'rs')
            ->where('rs.project = :project')
            ->setParameter('project', $project)
            ->getQuery()->execute();
        unset($qb);

        // Remove the connection between submissions and submit tokens
        $query = $this->entityManager->createQuery('
                UPDATE Mosparo\Entity\Submission s
                SET s.submitToken = NULL
                WHERE s.project = :project
            ')
            ->setParameter('project', $project);
        $query->execute();
        unset($query);

        // Delete the submit tokens
        $query = $this->entityManager->createQuery('
                DELETE Mosparo\Entity\SubmitToken st
                WHERE st.project = :project
            ')
            ->setParameter('project', $project);
        $query->execute();
        unset($query);

        // Delete the submissions
        $query = $this->entityManager->createQuery('
                DELETE Mosparo\Entity\Submission s
                WHERE s.project = :project
            ')
            ->setParameter('project', $project);
        $query->execute();
        unset($query);

        // Delete the day statistic
        $qb = $this->entityManager->createQueryBuilder();
        $qb->delete('Mosparo\Entity\DayStatistic', 'ds')
            ->where('ds.project = :project')
            ->setParameter('project', $project)
            ->getQuery()->execute();
        unset($qb);

        // Delete the security guidelines
        $qb = $this->entityManager->createQueryBuilder();
        $qb->delete('Mosparo\Entity\SecurityGuideline', 'sg')
            ->where('sg.project = :project')
            ->setParameter('project', $project)
            ->getQuery()->execute();
        unset($qb);
    }

    /**
     * Cleanup the IP localization cache. This will be executed after the
     * GeoIP2 database is refreshed.
     *
     * @return void
     */
    public function cleanupIpLocalizationCache()
    {
        $qb = $this->entityManager->createQueryBuilder();
        $qb->delete('Mosparo\Entity\IpLocalization', 'il')
            ->getQuery()->execute();
    }

    /**
     * Delete all submissions without an assigned submit token
     * and without submit tokens referencing the submission.
     *
     * @return void
     */
    protected function deleteSubmissionsWithoutAssignedSubmitTokens(CleanupStatistic $cleanupStatistic)
    {
        $maxResults = 50;

        // The limiter prevents possible endless loops.
        for ($limiter = 0; $limiter < 1000; $limiter++) {
            $query = $this->entityManager->createQuery('
                    SELECT s.id
                    FROM Mosparo\Entity\Submission s
                    WHERE s.submitToken IS NULL
                    AND NOT EXISTS (
                        SELECT 1 FROM Mosparo\Entity\SubmitToken st WHERE st.lastSubmission = s.id
                    )
                ')
                ->setMaxResults($maxResults);
            $submissionIds = $query->getResult();
            if (!$submissionIds) {
                break;
            }

            $deleteQuery = $this->entityManager->createQuery('
                    DELETE Mosparo\Entity\Submission s
                    WHERE s.id IN (:ids)
                ')
                ->setParameter('ids', $submissionIds);
            $deleteQuery->execute();
            unset($deleteQuery);

            $cleanupStatistic->increaseNumberOfDeletedSubmissions(count($submissionIds));

            if (count($submissionIds) < $maxResults) {
                break;
            }
        }

        unset($query);
        unset($submissionIds);
    }

    /**
     * Delete all submit tokens which are not connected with a
     * submission and are older than one day.
     *
     * @return void
     */
    protected function deleteSubmitTokensWithoutSubmissions(CleanupStatistic $cleanupStatistic)
    {
        $maxResults = 50;

        // The limiter prevents possible endless loops.
        for ($limiter = 0; $limiter < 1000; $limiter++) {
            $query = $this->entityManager->createQuery('
                    SELECT st.id
                    FROM Mosparo\Entity\SubmitToken st
                    WHERE st.createdAt < :limit
                    AND NOT EXISTS (
                        SELECT 1 FROM Mosparo\Entity\Submission s WHERE s.submitToken = st.id
                    )
                ')
                ->setParameter('limit', (new DateTime())->sub($this->submitTokenRetentionPeriod))
                ->setMaxResults($maxResults);
            $submitTokenIds = $query->getResult();
            if (!$submitTokenIds) {
                break;
            }

            $deleteQuery = $this->entityManager->createQuery('
                    DELETE Mosparo\Entity\SubmitToken st
                    WHERE st.id IN (:ids)
                ')
                ->setParameter('ids', $submitTokenIds);
            $deleteQuery->execute();
            unset($deleteQuery);

            $cleanupStatistic->increaseNumberOfDeletedSubmitTokens(count($submitTokenIds));

            if (count($submitTokenIds) < $maxResults) {
                break;
            }
        }

        unset($query);
        unset($submitTokenIds);
    }

    public function getLastDatabaseCleanup(): ?DateTime
    {
        $query = $this->entityManager
            ->createQuery('SELECT cs.dateTime FROM Mosparo\Entity\CleanupStatistic cs ORDER BY cs.dateTime DESC')
            ->setMaxResults(1);
        $results = $query->getSingleColumnResult();

        if (!$results) {
            return null;
        }

        return new DateTime(current($results));
    }

    public function hasUnfinshedCleanupStatistics(): bool
    {
        $qb = $this->entityManager->createQueryBuilder()
            ->select('cs.id')
            ->from(CleanupStatistic::class, 'cs')
            ->where('cs.cleanupStatus = :statusUnknown')
            ->andWhere('cs.dateTime > :minTime')
            ->setParameter('statusUnknown', CleanupStatus::UNKNOWN)
            ->setParameter('minTime', (new DateTime())->sub($this->cleanupUnfinishedInterval))
            ->setMaxResults(1)
        ;

        return ($qb->getQuery()->getOneOrNullResult() !== null);
    }
}