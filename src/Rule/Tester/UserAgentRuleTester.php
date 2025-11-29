<?php

namespace Mosparo\Rule\Tester;

use Doctrine\ORM\Query\Expr\Orx;
use Doctrine\ORM\QueryBuilder;
use Kir\StringUtils\Matching\Wildcards\Pattern;
use Mosparo\Rule\RuleItemEntityInterface;

class UserAgentRuleTester extends AbstractRuleTester
{
    public function buildExpressions(QueryBuilder $qb, Orx $orExpr, array $fieldData, ?string $value)
    {
        $orExpr->add($qb->expr()->andX()
            ->add($qb->expr()->eq('i.type', $qb->createNamedParameter('uaText')))
            ->add($qb->expr()->like($qb->createNamedParameter($value), 'i.preparedValue'))
        );

        $orExpr->add($qb->expr()->eq('i.type', $qb->createNamedParameter('uaRegex')));
    }

    public function validateData(string $key, mixed $lowercaseValue, mixed $originalValue, RuleItemEntityInterface $item): array
    {
        $matchingItems = [];
        $result = false;
        if ($item->getType() === 'uaText') {
            $result = $this->validateTextItem($lowercaseValue, $item->getValue());
        } else if ($item->getType() === 'uaRegex') {
            $result = $this->validateRegexItem($originalValue, $item->getValue());
        }

        if ($result !== false) {
            $matchingItems = [
                'type' => $item->getType(),
                'value' => $item->getValue(),
                'rating' => $this->calculateSpamRating($item),
                'uuid' => $item->getParent()->getUuid(),
            ];
        }

        return $matchingItems;
    }

    protected function validateTextItem($value, $itemValue): bool
    {
        $itemValue = strtolower($itemValue);

        if (strpos($itemValue, '*') !== false || strpos($itemValue, '?') !== false) {
            $pattern = '*' . trim($itemValue, '*') . '*';
            $value = str_replace("\n", ' ', $value);

            if (Pattern::create($pattern)->match($value)) {
                return true;
            }
        } else if (strpos($value, $itemValue) !== false) {
            return true;
        }

        return false;
    }

    protected function validateRegexItem($value, $itemValue): ?bool
    {
        return (@preg_match($itemValue, $value));
    }
}