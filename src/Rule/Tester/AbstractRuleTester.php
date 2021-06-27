<?php

namespace Mosparo\Rule\Tester;

use Mosparo\Entity\RulesetRuleCache;
use Mosparo\Rule\RuleEntityInterface;

abstract class AbstractRuleTester implements RuleTesterInterface
{
    protected function calculateSpamRating(RuleEntityInterface $rule, $item, $additionalFactor = 1): float
    {
        $rating = 1;
        if (isset($item['rating']) && $item['rating'] != '') {
            $rating = $item['rating'];
        }

        $ruleSpamRatingFactor = 1;
        if ($rule->getSpamRatingFactor() != '') {
            $ruleSpamRatingFactor = $rule->getSpamRatingFactor();
        }

        $rating = ($rating * $additionalFactor) * $ruleSpamRatingFactor;

        if ($rule instanceof RulesetRuleCache) {
            $rating = $rating * $rule->getRulesetCache()->getRuleset()->getSpamRatingFactor();
        }

        return $rating;
    }
}