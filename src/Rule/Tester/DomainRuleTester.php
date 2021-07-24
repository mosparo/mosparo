<?php

namespace Mosparo\Rule\Tester;

use Mosparo\Rule\RuleEntityInterface;

class DomainRuleTester extends AbstractRuleTester
{
    public function validateData($key, $value, RuleEntityInterface $rule): array
    {
        $matchingItems = [];
        foreach ($rule->getItems() as $item) {
            $value = strtolower($value);
            $itemValue = strtolower($item->getValue());

            $pattern = '/(.|\/\/|@)' . str_replace('.', '\\.', trim($itemValue, './')) . '/is';
            if (preg_match($pattern, $value)) {
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