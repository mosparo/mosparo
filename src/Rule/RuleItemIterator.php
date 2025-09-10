<?php

namespace Mosparo\Rule;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Mosparo\Entity\RuleItem;
use Mosparo\Entity\RulePackageRuleItemCache;

class RuleItemIterator implements \Iterator
{
    protected EntityManagerInterface $entityManager;

    protected QueryBuilder $baseQuery;

    protected bool $useRules = true;

    protected bool $useRulePackages = true;

    protected ?array $predefinedRuleItemIds;

    protected ?iterable $iterator = null;

    protected bool $processedRules = false;

    protected bool $processedRulePackages = false;

    public function __construct(EntityManagerInterface $entityManager, QueryBuilder $baseQuery, $useRules = true, $useRulePackages = true, ?array $predefinedRuleItemIds = null)
    {
        $this->entityManager = $entityManager;
        $this->baseQuery = $baseQuery;
        $this->useRules = $useRules;
        $this->useRulePackages = $useRulePackages;
        $this->predefinedRuleItemIds = $predefinedRuleItemIds;
    }

    /**
     * @inheritDoc
     */
    public function current(): mixed
    {
        $this->prepareIterator();

        if ($this->iterator) {
            return $this->iterator->current();
        }

        return null;
    }

    /**
     * @inheritDoc
     */
    public function next(): void
    {
        $this->prepareIterator();

        if ($this->iterator) {
            $this->iterator->next();
        }
    }

    /**
     * @inheritDoc
     */
    public function key(): mixed
    {
        $this->prepareIterator();

        if ($this->iterator) {
            return $this->iterator->key();
        }

        return null;
    }

    /**
     * @inheritDoc
     */
    public function valid(): bool
    {
        $this->prepareIterator();

        if (!$this->iterator->valid()) {
            $this->iterator = null;

            $this->prepareIterator();

            if (!$this->iterator) {
                return false;
            }

            $this->iterator->rewind();

            return $this->iterator->valid();
        }

        if (!$this->iterator) {
            return false;
        }

        return $this->iterator->valid();
    }

    /**
     * @inheritDoc
     */
    public function rewind(): void
    {
        $this->prepareIterator();

        if ($this->iterator) {
            $this->iterator->rewind();
        }
    }

    public function detach(mixed $ruleItem)
    {
        $this->entityManager->detach($ruleItem);
    }

    protected function prepareIterator()
    {
        if ($this->iterator === null) {
            if (!$this->processedRules && $this->useRules) {
                $qb = (clone $this->baseQuery)
                    ->from(RuleItem::class, 'i')
                ;

                if ($this->predefinedRuleItemIds && isset($this->predefinedRuleItemIds['ri'])) {
                    $qb->andWhere($qb->expr()->in('i.id', $qb->createNamedParameter($this->predefinedRuleItemIds['ri'])));
                }

                $this->iterator = $qb->getQuery()->toIterable();
                $this->processedRules = true;
            } else if (!$this->processedRulePackages && $this->useRulePackages) {
                $qb = (clone $this->baseQuery)
                    ->from(RulePackageRuleItemCache::class, 'i')
                ;

                if ($this->predefinedRuleItemIds && isset($this->predefinedRuleItemIds['rpric'])) {
                    $qb->andWhere($qb->expr()->in('i.id', $qb->createNamedParameter($this->predefinedRuleItemIds['rpric'])));
                }

                $this->iterator = $qb->getQuery()->toIterable();
                $this->processedRulePackages = true;
            }
        }
    }
}