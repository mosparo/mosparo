<?php

namespace Mosparo\Rule\Tester;

use Doctrine\ORM\Query\Expr\Orx;
use Doctrine\ORM\QueryBuilder;
use Mosparo\Rule\RuleItemEntityInterface;

interface RuleTesterInterface
{
    public function buildExpressions(QueryBuilder $qb, Orx $orExpr, array $fieldData, ?string $value);

    public function validateData($key, $value, RuleItemEntityInterface $item): array;
}