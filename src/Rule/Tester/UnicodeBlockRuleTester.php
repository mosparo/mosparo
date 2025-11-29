<?php

namespace Mosparo\Rule\Tester;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\ORM\Query\Expr\Orx;
use Doctrine\ORM\QueryBuilder;
use Mosparo\Rule\RuleItemEntityInterface;
use Mosparo\Util\HashUtil;
use zepi\Unicode\UnicodeIndex;

class UnicodeBlockRuleTester extends AbstractRuleTester
{
    protected array $blocks = [];

    public function buildExpressions(QueryBuilder $qb, Orx $orExpr, array $fieldData, ?string $value)
    {
        if (!$this->blocks) {
            $unicodeIndex = new UnicodeIndex();
            $this->blocks = $unicodeIndex->getBlocks();
        }

        $blocks = [];
        foreach ($this->blocks as $block) {
            if (preg_match($block->getRegex(), $value)) {
                $blocks[] = HashUtil::hashFast($block->getKey());
            }
        }

        $orExpr->add($qb->expr()->andX()
            ->add($qb->expr()->eq('i.type', $qb->createNamedParameter('block')))
            ->add($qb->expr()->in('i.hashedValue', $qb->createNamedParameter($blocks, ArrayParameterType::STRING)))
        );
    }

    public function validateData(string $key, mixed $lowercaseValue, mixed $originalValue, RuleItemEntityInterface $item): array
    {
        $unicodeIndex = new UnicodeIndex();

        $matchingItems = [];
        $key = $item->getValue();
        $block = $unicodeIndex->getBlockByKey($key);

        if ($block === null) {
            return [];
        }

        // Use the original value to correctly match the Unicode Block
        if (preg_match($block->getRegex(), $originalValue)) {
            $matchingItems = [
                'type' => $item->getType(),
                'value' => $item->getValue(),
                'rating' => $this->calculateSpamRating($item),
                'uuid' => $item->getParent()->getUuid(),
            ];
        }

        return $matchingItems;
    }
}