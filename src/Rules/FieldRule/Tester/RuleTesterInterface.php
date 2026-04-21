<?php

namespace Mosparo\Rules\FieldRule\Tester;

use Doctrine\ORM\Query\Expr\Orx;
use Doctrine\ORM\QueryBuilder;
use Mosparo\Rules\FieldRule\RuleItemEntityInterface;

interface RuleTesterInterface
{
    public function buildExpressions(QueryBuilder $qb, Orx $orExpr, array $fieldData, ?string $value);

    public function validateData(string $key, mixed $lowercaseValue, mixed $originalValue, RuleItemEntityInterface $item): array;
}