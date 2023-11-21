<?php

namespace Mosparo\Helper;

use DateInterval;
use DateTime;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\ORM\EntityManagerInterface;
use Mosparo\Entity\Project;
use Mosparo\Util\DateRangeUtil;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;

class CleanupHelper
{
    protected EntityManagerInterface $entityManager;

    protected ProjectHelper $projectHelper;

    public function __construct(EntityManagerInterface $entityManager, ProjectHelper $projectHelper)
    {
        $this->entityManager = $entityManager;
        $this->projectHelper = $projectHelper;
    }

    public function cleanup($maxIterations = 10, $force = false)
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

        // Disable the project filters for the cleanup
        $filters = $this->entityManager->getFilters();
        $filterEnabled = false;
        if ($filters->isEnabled('project_related_filter')) {
            $filters->disable('project_related_filter');
            $filterEnabled = true;
        }

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
                    WHERE (s.submittedAt < :limit OR (s.submittedAt < :limitDay AND s.spam = 0 AND s.valid IS NULL))
                ')
                ->setParameter('limit', (new DateTime())->sub(new DateInterval('P14D')))
                ->setParameter('limitDay', (new DateTime())->sub(new DateInterval('PT24H')))
                ->setMaxResults($maxPerIteration);

            $result = $query->getResult();
            $deletableSubmissionIds = array_column($result,'id');
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
            if ($maxIterations > 1 && (microtime(true) - $startTime) > 1.5) {
                break;
            }
        }

        // Delete the submissions without submit token
        $query = $this->entityManager->createQuery('
                DELETE Mosparo\Entity\Submission s
                WHERE s.submitToken IS NULL
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

        // Clear the day statistic
        $this->cleanupDayStatistcs();

        // Enable the project filters after the cleanup
        if ($filterEnabled) {
            $filters
                ->enable('project_related_filter')
                ->setProjectHelper($this->projectHelper);
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
        $query = $this->entityManager->createQuery('
                DELETE Mosparo\Entity\DayStatistic ds
                WHERE ds.project = :project
            ')
            ->setParameter('project', $project);
        $query->execute();
        unset($query);
    }

    public function cleanupIpLocalizationCache()
    {
        $qb = $this->entityManager->createQueryBuilder();
        $qb->delete('Mosparo\Entity\IpLocalization', 'il')
            ->getQuery()->execute();
    }
}