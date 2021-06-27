<?php

namespace Mosparo\Rule\Tester;

use Mosparo\Rule\RuleEntityInterface;

class DomainRuleTester extends AbstractRuleTester
{
    public function validateData($key, $value, RuleEntityInterface $rule): array
    {
        $matchingItems = [];
        foreach ($rule->getItems() as $item) {
            $pattern = '/(.|\/\/|@)' . str_replace('.', '\\.', trim($item['value'], './')) . '/is';
            if (preg_match($pattern, $value)) {
                $matchingItems[] = [
                    'type' => $item['type'],
                    'value' => $item['value'],
                    'rating' => $this->calculateSpamRating($rule, $item),
                    'uuid' => $rule->getUuid()
                ];
            }
        }

        return $matchingItems;
    }
}