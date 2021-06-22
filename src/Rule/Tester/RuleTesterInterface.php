<?php

namespace Mosparo\Rule\Tester;

use Mosparo\Entity\Rule;

interface RuleTesterInterface
{
    public function validateData($key, $value, Rule $rule): array;
}