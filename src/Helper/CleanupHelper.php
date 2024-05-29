<?php

namespace Mosparo\Helper;

use DateInterval;
use DateTime;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\ORM\EntityManagerInterface;
use Mosparo\Entity\Project;
use Mosparo\Util\DateRangeUtil;
use Psr\Log\LoggerInterface;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;

class CleanupHelper
{
    protected EntityManagerInterface $entityManager;

    protected ProjectHelper $projectHelper;

    protected LoggerInterface $logger;

    public function __construct(EntityManagerInterface $entityManager, ProjectHelper $projectHelper, LoggerInterface $logger)
    {
        $this->entityManager = $entityManager;
        $this->projectHelper = $projectHelper;
        $this->logger = $logger;
    }

    public function cleanup($maxIterations = 10, $force = false, $ignoreExceptions = true, $timeout = 1.5)
    {
        $cache = new FilesystemAdapter();
        $nextCleanup = $cache->getItem('mosparoNextCleanup');
        $cleanupStartedAt = $cache->getItem('mosparoCleanupStartedAt');

        // If the force parameter is set, we execute the cleanup anyways
        if ($nextCleanup->get() !== null && !$force) {
            // Return, if the next cleanup date is in the future
            if ($nextCleanup->get() > new DateTime()) {
                return;
            }

            // Do not start the cleanup if another request is already executing the cleanup
            if ($cleanupStartedAt->get() !== null && $cleanupStartedAt->get() > (new DateTime())->sub(new DateInterval('PT5M'))) {
                return;
            }
        }

        $maxPerIteration = 2500;
        $notFinished = true;
        $startTime = microtime(true);

        // Lock the cleanup
        $cleanupStartedAt->set(new DateTime());
        $cache->save($cleanupStartedAt);

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
            $qb->delete('Mosparo\Entity\Delay', 'd')
                ->where('d.validUntil < :now')
                ->setParameter('now', new DateTime())
                ->getQuery()->execute();
            unset($qb);

            // Delete expired lockouts
            $qb = $this->entityManager->createQueryBuilder();
            $qb->delete('Mosparo\Entity\Lockout', 'l')
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

                // Delete the submit tokens
                $query = $this->entityManager->createQuery('
                        DELETE Mosparo\Entity\SubmitToken st
                        WHERE st.id IN (:deletableSubmitTokenIds)
                    ')
                    ->setParameter('deletableSubmitTokenIds', $deletableSubmitTokenIds, ArrayParameterType::INTEGER);
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

                // If it took more than 1.5 seconds, stop the cleanup
                if ($maxIterations > 1 && (microtime(true) - $startTime) > $timeout) {
                    break;
                }
            }

            // Delete the submissions without submit token
            $query = $this->entityManager->createQuery('
                    DELETE Mosparo\Entity\Submission s
                    WHERE s.submitToken IS NULL
                    AND (SELECT COUNT(st.id) FROM Mosparo\Entity\SubmitToken st WHERE st.lastSubmission = s.id) = 0
                ');
            $query->execute();
            unset($query);

            // Delete the submit tokens without submission and older than one day
            $query = $this->entityManager->createQuery('
                    DELETE Mosparo\Entity\SubmitToken st
                    WHERE st.createdAt < :limit
                    AND (SELECT COUNT(s.id) FROM Mosparo\Entity\Submission s WHERE s.submitToken = st.id) = 0
                ')
                ->setParameter('limit', (new DateTime())->sub(new DateInterval('PT24H')));
            $query->execute();
            unset($query);
        } catch (\Exception $e) {
            // Throw the exception if we should not ignore exceptions.
            if (!$ignoreExceptions) {
                throw $e;
            }

            $this->logger->critical($e->getMessage());

            // Since there was an error, let's try that again in 10 minutes
            $notFinished = true;
        }

        // Clear the day statistic
        $this->cleanupDayStatistcs();

        // Set the active project after the cleanup
        if ($activeProject !== null) {
            $this->projectHelper->setActiveProject($activeProject);
        }

        $nextCleanupDate = new DateTime();
        if ($notFinished) {
            // Execute the next cleanup in 10 minutes
            // We give the (database) server these 10 minutes to relax after deleting so many rows.
            $nextCleanupDate->add(new DateInterval('PT10M'));
        } else {
            // If the cleanup process was finished, perform the next cleanup in 6 hours
            $nextCleanupDate->add(new DateInterval('PT6H'));
        }

        $nextCleanup->set($nextCleanupDate);
        $cache->save($nextCleanup);

        $cleanupStartedAt->set(null);
        $cache->save($cleanupStartedAt);
    }

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

    public function cleanupProjectEntities($project)
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

    public function cleanupIpLocalizationCache()
    {
        $qb = $this->entityManager->createQueryBuilder();
        $qb->delete('Mosparo\Entity\IpLocalization', 'il')
            ->getQuery()->execute();
    }
}