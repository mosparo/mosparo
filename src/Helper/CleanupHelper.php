<?php

namespace Mosparo\Helper;

use DateInterval;
use DateTime;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\ORM\EntityManagerInterface;
use Mosparo\Entity\CleanupStatistic;
use Mosparo\Entity\Project;
use Mosparo\Enum\CleanupExecutor;
use Mosparo\Enum\CleanupStatus;
use Mosparo\Util\DateRangeUtil;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Cache\CacheInterface;

class CleanupHelper
{
    const DURATION_REGULAR = 'PT6H';
    const DURATION_ADDITIONAL = 'PT10M';

    protected EntityManagerInterface $entityManager;

    protected ProjectHelper $projectHelper;

    protected LoggerInterface $logger;

    protected CacheInterface $cache;

    protected bool $cleanupGracePeriodEnabled;

    public function __construct(EntityManagerInterface $entityManager, ProjectHelper $projectHelper, LoggerInterface $logger, CacheInterface $cache, bool $cleanupGracePeriodEnabled = false)
    {
        $this->entityManager = $entityManager;
        $this->projectHelper = $projectHelper;
        $this->logger = $logger;
        $this->cache = $cache;
        $this->cleanupGracePeriodEnabled = $cleanupGracePeriodEnabled;
    }

    public function cleanup($maxIterations = 10, $force = false, $ignoreExceptions = true, $timeout = 1.5, $cleanupExecutor = CleanupExecutor::UNKNOWN)
    {
        $nextCleanup = $this->cache->getItem('mosparoNextCleanup');
        $additionalCleanup = $this->cache->getItem('mosparoAdditionalCleanup');
        $cleanupStartedAt = $this->cache->getItem('mosparoCleanupStartedAt');

        // If the force parameter is set, we execute the cleanup anyways
        if (!$force) {
            // Clone the DateTime object to keep the original time because we manipulate the time later (see below).
            if ($nextCleanup->get() !== null) {
                $cleanupStart = clone $nextCleanup->get();
            } else {
                $cleanupStart = $this->getLastDatabaseCleanup()->add(new DateInterval(self::DURATION_REGULAR));
            }

            // Add the cleanup grace period - if enabled - to the regular cleanup time but not the
            // additional cleanup (see below).
            if ($this->cleanupGracePeriodEnabled) {
                $cleanupStart->add(new DateInterval('PT24H'));
            }

            // Check if there is an additional cleanup needed from the last cleanup run. It will override all
            // the other times.
            if ($additionalCleanup->get() !== null) {
                $cleanupStart = $additionalCleanup->get();
            }

            // Return, if the next cleanup date is in the future
            if ($cleanupStart > new DateTime()) {
                return;
            }

            // Do not start the cleanup if another request is already executing the cleanup
            if ($cleanupStartedAt->get() !== null && $cleanupStartedAt->get() > (new DateTime())->sub(new DateInterval('PT5M'))) {
                return;
            }
        }

        // Log the start of the cleanup process
        $this->logger->info(sprintf(
            'Start cleanup process (Max iterations: %d; Force: %b; Ignore exceptions: %b, Timeout: %01.1fs)',
            $maxIterations,
            $force,
            $ignoreExceptions,
            $timeout
        ));

        $cleanupStatistic = (new CleanupStatistic())
            ->setCleanupExecutor($cleanupExecutor);

        $maxPerIteration = 2500;
        $notFinished = true;
        $startTime = microtime(true);

        // Lock the cleanup
        $cleanupStartedAt->set(new DateTime());
        $this->cache->save($cleanupStartedAt);

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
                    ->setParameter('limit', (new DateTime())->sub(new DateInterval('P14D')))
                    ->setParameter('limitDay', (new DateTime())->sub(new DateInterval('PT24H')))
                    ->setMaxResults($maxPerIteration);

                $result = $query->getResult();
                $deletableSubmissionIds = array_column($result, 'id');
                $deletableSubmitTokenIds = array_unique(array_column($result, 'stId'));
                unset($query);
                unset($result);

                if (count($deletableSubmissionIds) === 0) {
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
                    ->setParameter('deletableSubmissionIds', $deletableSubmissionIds, ArrayParameterType::INTEGER);
                $query->execute();
                unset($query);

                // If  a submit token was used in multiple submissions, but only one was verified, the SELECT query
                // above might think that the submit token is not required anymore. But the verified submission
                // is not deletable yet and requires the submit token, so we must keep that one.
                $query = $this->entityManager->createQuery('
                        SELECT st.id 
                        FROM Mosparo\Entity\Submission s
                        JOIN s.submitToken st
                        WHERE st.id IN (:deletableSubmitTokenIds)
                    ')
                    ->setParameter('deletableSubmitTokenIds', $deletableSubmitTokenIds, ArrayParameterType::INTEGER);
                $result = array_unique($query->getSingleColumnResult());
                unset($query);
                $reallyDeletableSubmitTokenIds = array_diff($deletableSubmitTokenIds, $result);

                // Delete the submit tokens
                $query = $this->entityManager->createQuery('
                        DELETE Mosparo\Entity\SubmitToken st
                        WHERE st.id IN (:deletableSubmitTokenIds)
                    ')
                    ->setParameter('deletableSubmitTokenIds', $reallyDeletableSubmitTokenIds, ArrayParameterType::INTEGER);
                $query->execute();
                unset($query);

                // Delete the submissions
                $query = $this->entityManager->createQuery('
                        DELETE Mosparo\Entity\Submission s
                        WHERE s.id IN (:deletableSubmissionIds)
                    ')
                    ->setParameter('deletableSubmissionIds', $deletableSubmissionIds, ArrayParameterType::INTEGER);
                $query->execute();
                unset($query);

                $cleanupStatistic
                    ->increaseNumberOfDeletedSubmitTokens(count($reallyDeletableSubmitTokenIds))
                    ->increaseNumberOfDeletedSubmissions(count($deletableSubmissionIds));

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
            $this->deleteSubmissionArtefacts($cleanupStatistic);
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
            $additionalCleanupDate = (new DateTime())->add(new DateInterval(self::DURATION_ADDITIONAL));
            $cleanupStatistic->setCleanupStatus(CleanupStatus::INCOMPLETE);
        } else {
            $cleanupStatistic->setCleanupStatus(CleanupStatus::COMPLETE);
        }

        $additionalCleanup->set($additionalCleanupDate);
        $this->cache->save($additionalCleanup);

        // The next regular cleanup will be performed in 6 hours
        $nextCleanup->set((new DateTime())->add(new DateInterval(self::DURATION_REGULAR)));
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

        // Delete all rulesets rule item cache
        $qb = $this->entityManager->createQueryBuilder();
        $qb->delete('Mosparo\Entity\RulesetRuleItemCache', 'rsric')
            ->where('rsric.project = :project')
            ->setParameter('project', $project)
            ->getQuery()->execute();
        unset($qb);

        // Delete all rulesets rule cache
        $qb = $this->entityManager->createQueryBuilder();
        $qb->delete('Mosparo\Entity\RulesetRuleCache', 'rsrc')
            ->where('rsrc.project = :project')
            ->setParameter('project', $project)
            ->getQuery()->execute();
        unset($qb);

        // Delete all rulesets cache
        $qb = $this->entityManager->createQueryBuilder();
        $qb->delete('Mosparo\Entity\RulesetCache', 'rsc')
            ->where('rsc.project = :project')
            ->setParameter('project', $project)
            ->getQuery()->execute();
        unset($qb);

        // Delete all rulesets
        $qb = $this->entityManager->createQueryBuilder();
        $qb->delete('Mosparo\Entity\Ruleset', 'rs')
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
     * Delete the submissions where the submit token does no longer exist
     * This situation should not happen normally, but this query will clean up the database in case it happens.
     * This query is not limited by the time like other queries because if the submit token is missing,
     * the submission is incomplete and will throw an exception in the administration interface.
     *
     * @return void
     */
    protected function deleteSubmissionArtefacts(CleanupStatistic $cleanupStatistic): void
    {
        $maxResults = 50;

        // The limiter prevents possible endless loops.
        for ($limiter = 0; $limiter < 1000; $limiter++) {
            $query = $this->entityManager->createQuery('
                    SELECT s.id
                    FROM Mosparo\Entity\Submission s
                    WHERE NOT EXISTS (
                        SELECT 1 FROM Mosparo\Entity\SubmitToken st
                        WHERE st.id = s.submitToken OR st.lastSubmission = s.id
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
                ->setParameter('limit', (new DateTime())->sub(new DateInterval('PT24H')))
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
}