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
            if ($item->getType() === $key) {
                $result = ($item->getValue() == $value);
            }

            if ($result !== false) {
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