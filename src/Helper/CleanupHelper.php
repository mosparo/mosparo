<?php

namespace Mosparo\Helper;

use DateInterval;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Mosparo\Entity\Project;
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

    public function cleanup($force = false)
    {
        $cache = new FilesystemAdapter();
        $lastCleanup = $cache->getItem('mosparoLastCleanup');

        // If the force parameter is not set, we execute the cleanup only once every 24 hours
        if ($lastCleanup->get() !== null && !$force) {
            $dayAgo = (new DateTime())->sub(new DateInterval('P1D'));

            if ($lastCleanup->get() > $dayAgo) {
                return;
            }
        }

        // Delete expired delays
        $qb = $this->entityManager->createQueryBuilder();
        $qb->delete('Mosparo\Entity\Delay', 'd')
            ->where('d.validUntil < :now')
            ->setParameter('now', new DateTime())
            ->getQuery()->execute();

        // Delete expired lockouts
        $qb = $this->entityManager->createQueryBuilder();
        $qb->delete('Mosparo\Entity\Lockout', 'l')
             ->where('l.validUntil < :now')
             ->setParameter('now', new DateTime())
             ->getQuery()->execute();

        // Save the active project
        $activeProject = $this->projectHelper->getActiveProject();
        $projectRepository = $this->entityManager->getRepository(Project::class);
        foreach ($projectRepository->findAll() as $project) {
            // Clear the cache before starting with the project cleanup process
            $this->entityManager->getConfiguration()->getQueryCache()->clear();
            $this->entityManager->getConfiguration()->getResultCache()->clear();

            // Set the active project for the filter
            $this->projectHelper->setActiveProject($project);

            // Delete all submissions which were submitted more than 14 days ago or more than 24 hours ago but never validated
            $qb = $this->entityManager->createQueryBuilder();
            $qb->select('s')
                ->from('Mosparo\Entity\Submission', 's')

                // The WHERE part is encapsulated by an extra set of brackets because of the general project filter which
                // is added at the end of the WHERE with "AND project_id = ?" (see Mosparo\Doctrine\ProjectRelatedFilter).
                ->where('(s.submittedAt < :limit OR (s.submittedAt < :limitDay AND s.spam = 0 AND s.valid IS NULL))')

                ->setParameter('limit', (new DateTime())->sub(new DateInterval('P14D')))
                ->setParameter('limitDay', (new DateTime())->sub(new DateInterval('PT24H')));

            foreach ($qb->getQuery()->getResult() as $submission) {
                $submission->setSubmitToken(null);
                $this->entityManager->remove($submission);
            }

            $this->entityManager->flush();

            // Delete all submit tokens which were created more than 24 hours ago and weren't used in a submission
            $qb = $this->entityManager->createQueryBuilder();
            $qb->delete('Mosparo\Entity\SubmitToken', 'st')
                ->where('st.createdAt < :limit')
                ->andWhere('st.submission IS NULL')
                ->setParameter('limit', (new DateTime())->sub(new DateInterval('PT24H')))
                ->getQuery()->execute();
        }

        $lastCleanup->set(new DateTime());
        $cache->save($lastCleanup);

        // Restore the active project
        $this->projectHelper->setActiveProject($activeProject);
    }

    public function cleanupProjectEntities($project)
    {
        // Delete all rule items
        $qb = $this->entityManager->createQueryBuilder();
        $qb->delete('Mosparo\Entity\RuleItem', 'ri')
            ->where('ri.project = :project')
            ->setParameter('project', $project)
            ->getQuery()->execute();

        // Delete all rules
        $qb = $this->entityManager->createQueryBuilder();
        $qb->delete('Mosparo\Entity\Rule', 'r')
            ->where('r.project = :project')
            ->setParameter('project', $project)
            ->getQuery()->execute();

        // Delete all rulesets rule item cache
        $qb = $this->entityManager->createQueryBuilder();
        $qb->delete('Mosparo\Entity\RulesetRuleItemCache', 'rsric')
            ->where('rsric.project = :project')
            ->setParameter('project', $project)
            ->getQuery()->execute();

        // Delete all rulesets rule cache
        $qb = $this->entityManager->createQueryBuilder();
        $qb->delete('Mosparo\Entity\RulesetRuleCache', 'rsrc')
            ->where('rsrc.project = :project')
            ->setParameter('project', $project)
            ->getQuery()->execute();

        // Delete all rulesets cache
        $qb = $this->entityManager->createQueryBuilder();
        $qb->delete('Mosparo\Entity\RulesetCache', 'rsc')
            ->where('rsc.project = :project')
            ->setParameter('project', $project)
            ->getQuery()->execute();

        // Delete all rulesets
        $qb = $this->entityManager->createQueryBuilder();
        $qb->delete('Mosparo\Entity\Ruleset', 'rs')
            ->where('rs.project = :project')
            ->setParameter('project', $project)
            ->getQuery()->execute();

        // Delete all submissions
        $qb = $this->entityManager->createQueryBuilder();
        $qb->select('s')
            ->from('Mosparo\Entity\Submission', 's')
            ->where('s.project = :project')
            ->setParameter('project', $project);

        foreach ($qb->getQuery()->getResult() as $submission) {
            $submission->setSubmitToken(null);
            $this->entityManager->remove($submission);
        }

        $this->entityManager->flush();

        // Delete all submit tokens
        $qb = $this->entityManager->createQueryBuilder();
        $qb->delete('Mosparo\Entity\SubmitToken', 'st')
            ->where('st.project = :project')
            ->setParameter('project', $project)
            ->getQuery()->execute();
    }

    public function cleanupIpLocalizationCache()
    {
        $qb = $this->entityManager->createQueryBuilder();
        $qb->delete('Mosparo\Entity\IpLocalization', 'il')
            ->getQuery()->execute();
    }
}