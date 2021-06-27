<?php

namespace Mosparo\Rule\Tester;

use Mosparo\Rule\RuleEntityInterface;

interface RuleTesterInterface
{
    public function validateData($key, $value, RuleEntityInterface $rule): array;
}