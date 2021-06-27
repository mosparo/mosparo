<?php

namespace Mosparo\Rule\Tester;

use Kir\StringUtils\Matching\Wildcards\Pattern;
use Mosparo\Rule\RuleEntityInterface;

class WordRuleTester extends AbstractRuleTester
{
    public function validateData($key, $value, RuleEntityInterface $rule): array
    {
        $matchingItems = [];
        foreach ($rule->getItems() as $item) {
            $result = false;
            if ($item['type'] === 'text') {
                $result = $this->validateTextItem($value, $item['value']);
            } else if ($item['type'] === 'regex') {
                $result = $this->validateRegexItem($value, $item['value']);
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

    protected function validateTextItem($value, $itemValue)
    {
        if (strpos($itemValue, '*') !== false || strpos($itemValue, '?') !== false) {
            $pattern = '*' . trim($itemValue, '*') . '*';
            $value = str_replace("\n", ' ', $value);

            if (Pattern::create($pattern)->match($value)) {
                return true;
            }
        } else if (strpos($value, $itemValue) !== false) {
            return true;
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