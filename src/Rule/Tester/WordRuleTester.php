<?php

namespace Mosparo\Rule\Tester;

use Mosparo\Entity\Rule;

class WordRuleTester extends AbstractRuleTester
{
    public function validateData($key, $value, Rule $rule): array
    {
        $matchingItems = [];
        foreach ($rule->getItems() as $item) {
            if ($item['type'] === 'text') {
                $result = $this->validateTextItem($value, $item['value']);
            } else if ($item['type'] === 'regex') {
                $result = $this->validateRegexItem($value, $item['value']);
            }

            if ($result) {
                $matchingItems[] = [
                    'type' => $item['type'],
                    'value' => $item['value'],
                    'rating' => $this->calculateSpamRating($rule, $item, $result)
                ];
            }
        }

        return $matchingItems;
    }

    protected function validateTextItem($value, $itemValue)
    {
        if (strpos($value, $itemValue) !== false) {
            return substr_count($value, $itemValue);
        }

        return false;
    }

    protected function validateRegexItem($value, $itemValue)
    {
        if (preg_match($itemValue, $value)) {
            return 1;
        }

        return false;
    }
}