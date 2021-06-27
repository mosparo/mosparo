<?php

namespace Mosparo\Rule\Tester;

use Mosparo\Rule\RuleEntityInterface;

class EmailRuleTester extends AbstractRuleTester
{
    public function validateData($key, $value, RuleEntityInterface $rule): array
    {
        $matchingItems = [];
        foreach ($rule->getItems() as $item) {
            if (strpos($value, $item['value']) !== false) {
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