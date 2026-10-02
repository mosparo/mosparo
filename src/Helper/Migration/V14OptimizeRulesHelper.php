<?php

namespace Mosparo\Helper\Migration;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Mosparo\Entity\Rule;
use Mosparo\Entity\RuleItem;
use Mosparo\Helper\ProjectHelper;

class V14OptimizeRulesHelper
{
    protected EntityManagerInterface $entityManager;

    protected ProjectHelper $projectHelper;

    protected bool $isDebug;

    public function __construct(EntityManagerInterface $entityManager, ProjectHelper $projectHelper, $isDebug)
    {
        $this->entityManager = $entityManager;
        $this->projectHelper = $projectHelper;
        $this->isDebug = $isDebug;
    }

    public function hasOpenTasks(): bool
    {
        return ($this->countOpenTasks() > 0);
    }

    public function countOpenTasks(): int
    {
        $qb = $this->createUserAgentRuleQueryBuilder()
            ->select('COUNT(i.id)')
        ;
        $count = $qb->getQuery()->getSingleScalarResult();

        return $count;
    }

    public function processOpenTasks(): int
    {
        $count = $this->optimizeUserAgentRules();

        return $count;
    }

    protected function optimizeUserAgentRules(): int
    {
        $counter = 0;
        $numberPerIteration = 1000;

        $qb = $this->createUserAgentRuleQueryBuilder();
        $qb->setMaxResults($numberPerIteration);

        $time = time();

        for ($idx = 0; $idx < 1000; $idx++) {
            $iterationCount = 0;
            $items = $qb->getQuery()->getResult();
            if (!$items) {
                break;
            }

            foreach ($items as $item) {
                if ($item->getType() === 'text') {
                    $item->setType('uaText');
                } else if ($item->getType() === 'regex') {
                    $item->setType('uaRegex');
                }
                $iterationCount++;
            }

            $this->entityManager->flush();

            foreach ($items as $item) {
                $this->entityManager->detach($item);
            }

            $this->entityManager->clear();

            $counter += $iterationCount;

            // No longer than 5 seconds (should save memory and does not end in a timeout)
            if ((time() - $time) > 5) {
                break;
            }

            // If debug is enabled, abort after using more than 32MB of Memory
            if ($this->isDebug && memory_get_usage() > 32 * 1024 * 1024) {
                break;
            }
        }

        return $counter;
    }

    protected function createUserAgentRuleQueryBuilder(): QueryBuilder
    {
        $qb = $this->entityManager->createQueryBuilder();

        $rulesQb = (clone $qb)
            ->select('r.id')
            ->from(Rule::class, 'r')
            ->where('r.type = :type')
        ;

        return $qb
            ->select('i')
            ->from(RuleItem::class, 'i')
            ->where($qb->expr()->in('i.rule', $rulesQb->getDQL()))
            ->andWhere('i.type IN (:types)')
            ->setParameter('type', 'user-agent')
            ->setParameter('types', ['text', 'regex'], ArrayParameterType::STRING)
        ;
    }
}