<?php

namespace Mosparo\Rule\Tester;

use Mosparo\Rule\RuleEntityInterface;

class ProviderRuleTester extends AbstractRuleTester
{
    public function validateData($key, $value, RuleEntityInterface $rule): array
    {
        $matchingItems = [];
        foreach ($rule->getItems() as $item) {
            $result = false;
            if ($item['type'] === $key) {
                $result = ($item['value'] == $value);
            }

            if ($result !== false) {
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