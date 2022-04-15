<?php

namespace Mosparo\Rule\Tester;

use Mosparo\Rule\RuleEntityInterface;

class WebsiteRuleTester extends AbstractRuleTester
{
    public function validateData($key, $value, RuleEntityInterface $rule): array
    {
        $matchingItems = [];
        foreach ($rule->getItems() as $item) {
            $preparedValue = $item->getValue();
            if (!preg_match('/((https?:)?\/\/)/is', $preparedValue)) {
                $preparedValue = '//' . $preparedValue;
            }

            $value = strtolower($value);
            $preparedValue = strtolower($preparedValue);

            if (strpos($value, $preparedValue) !== false) {
                $matchingItems[] = [
                    'type' => $item->getType(),
                    'value' => $item->getValue(),
                    'rating' => $this->calculateSpamRating($rule, $item),
                    'uuid' => $rule->getUuid()
                ];
            }
        }

        return $matchingItems;
    }
}