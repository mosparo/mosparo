<?php

namespace Mosparo\Rule\Tester;

use Mosparo\Entity\Rule;

class ProviderRuleTester extends AbstractRuleTester
{
    public function validateData($key, $value, Rule $rule): array
    {
        return [];
    }
}