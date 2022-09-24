<?php

namespace Mosparo\Rule\Tester;

use Mosparo\Rule\RuleEntityInterface;
use zepi\Unicode\UnicodeIndex;

class UnicodeBlockRuleTester extends AbstractRuleTester
{
    public function validateData($key, $value, RuleEntityInterface $rule): array
    {
        $unicodeIndex = new UnicodeIndex();

        $matchingItems = [];
        foreach ($rule->getItems() as $item) {
            $key = $item->getValue();
            $block = $unicodeIndex->getBlockByKey($key);

            if ($block === null) {
                continue;
            }

            if (preg_match($block->getRegex(), $value)) {
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