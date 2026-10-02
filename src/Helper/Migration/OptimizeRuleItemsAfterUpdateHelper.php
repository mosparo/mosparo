<?php

namespace Mosparo\Helper\Migration;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Mosparo\Entity\RuleItem;
use Mosparo\Entity\RulePackageRuleItemCache;
use Mosparo\Rules\FieldRule\RuleItemEntityInterface;

class OptimizeRuleItemsAfterUpdateHelper
{
    protected EntityManagerInterface $entityManager;

    protected bool $isDebug;

    public function __construct(EntityManagerInterface $entityManager, $isDebug)
    {
        $this->entityManager = $entityManager;
        $this->isDebug = $isDebug;
    }

    public function hasOpenTasks(): bool
    {
        return ($this->countOpenTasks() > 0);
    }

    public function countOpenTasks(): int
    {
        // Count the rule items
        $qb = $this->createQueryBuilder(RuleItem::class)
            ->select('COUNT(i.id)')
        ;
        $count = $qb->getQuery()->getSingleScalarResult();

        // Count the rule package items
        $qb = $this->createQueryBuilder(RulePackageRuleItemCache::class)
            ->select('COUNT(i.id)');
        $count += $qb->getQuery()->getSingleScalarResult();

        return $count;
    }

    public function processOpenTasks(): int
    {
        $count = $this->processItems($this->createQueryBuilder(RuleItem::class));

        if ($count === 0) {
            $count = $this->processItems($this->createQueryBuilder(RulePackageRuleItemCache::class));
        }

        return $count;
    }

    protected function processItems(QueryBuilder $qb): int
    {
        $counter = 0;
        $numberPerIteration = 1000;

        $qb
            ->setMaxResults($numberPerIteration);
        $time = time();

        for ($idx = 0; $idx < 1000; $idx++) {
            $iterationCount = 0;

            $items = $qb->getQuery()->getResult();
            if (!$items) {
                break;
            }
            $iterationCount += count($items);

            $this->entityManager->flush();
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

    protected function createQueryBuilder(string $class): QueryBuilder
    {
        return ($this->entityManager->createQueryBuilder())
            ->select('i')
            ->from($class, 'i')
            ->where('i.preparationVersion IS NULL OR i.preparationVersion < :preparationVersion')
            ->setParameter('preparationVersion', RuleItemEntityInterface::PREPARATION_VERSION)
        ;
    }
}