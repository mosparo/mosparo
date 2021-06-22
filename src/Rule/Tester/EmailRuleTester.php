<?php

namespace Mosparo\Rule\Tester;

use Mosparo\Entity\Rule;

class EmailRuleTester extends AbstractRuleTester
{
    public function validateData($key, $value, Rule $rule): array
    {
        return [];
    }
}