<?php

namespace Mosparo\Rule\Tester;

use Doctrine\ORM\Query\Expr\Orx;
use Doctrine\ORM\QueryBuilder;
use Mosparo\Rule\RuleItemEntityInterface;
use Mosparo\Util\HashUtil;

class ProviderRuleTester extends AbstractRuleTester
{
    public function buildExpressions(QueryBuilder $qb, Orx $orExpr, array $fieldData, ?string $value)
    {
        if ($fieldData['name'] === 'asNumber') {
            $orExpr->add($qb->expr()->andX()
                ->add($qb->expr()->eq('i.type', $qb->createNamedParameter('asNumber')))
                ->add($qb->expr()->eq('i.hashedValue', $qb->createNamedParameter(HashUtil::hashFast($value))))
            );
        } else if ($fieldData['name'] === 'country') {
            $orExpr->add($qb->expr()->andX()
                ->add($qb->expr()->eq('i.type', $qb->createNamedParameter('country')))
                ->add($qb->expr()->eq('i.hashedValue', $qb->createNamedParameter(HashUtil::hashFast($value))))
            );
        }
    }

    public function validateData($key, $value, RuleItemEntityInterface $item): array
    {
        $matchingItems = [];
        $result = false;
        if ($item->getType() === $key) {
            $result = ($item->getValue() == $value);
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
}