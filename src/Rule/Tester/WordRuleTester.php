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
            if ($item->getType() === 'text') {
                $result = $this->validateTextItem($value, $item->getValue());
            } else if ($item->getType() === 'regex') {
                $result = $this->validateRegexItem($value, $item->getValue());
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

    protected function validateTextItem($value, $itemValue): bool
    {
        $value = strtolower($value);
        $itemValue = strtolower($itemValue);

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

    protected function validateRegexItem($value, $itemValue): ?bool
    {
        return (@preg_match($itemValue, $value));
    }
}