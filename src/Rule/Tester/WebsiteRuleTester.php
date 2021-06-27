<?php

namespace Mosparo\Rule\Tester;

use Mosparo\Rule\RuleEntityInterface;

class WebsiteRuleTester extends AbstractRuleTester
{
    public function validateData($key, $value, RuleEntityInterface $rule): array
    {
        $matchingItems = [];
        foreach ($rule->getItems() as $item) {
            $preparedValue = $item['value'];
            if (!preg_match('/((https?\:)?\/\/)/is', $preparedValue)) {
                $preparedValue = '//' . $preparedValue;
            }

            if (strpos($value, $preparedValue) !== false) {
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