<?php

namespace Mosparo\Rule\Tester;

use Mosparo\Entity\RulesetRuleCache;
use Mosparo\Rule\RuleEntityInterface;

abstract class AbstractRuleTester implements RuleTesterInterface
{
    protected function calculateSpamRating(RuleEntityInterface $rule, $item, $additionalFactor = 1): float
    {
        $rating = 1;
        if (isset($item['rating']) && !empty($item['rating'])) {
            $rating = floatval($item['rating']);
        }

        $ruleSpamRatingFactor = 1;
        if (!empty($rule->getSpamRatingFactor())) {
            $ruleSpamRatingFactor = floatval($rule->getSpamRatingFactor());
        }

        $rating = ($rating * $additionalFactor) * $ruleSpamRatingFactor;

        if ($rule instanceof RulesetRuleCache && !empty($rule->getRulesetCache()->getRuleset()->getSpamRatingFactor())) {
            $rating = $rating * floatval($rule->getRulesetCache()->getRuleset()->getSpamRatingFactor());
        }

        return $rating;
    }
}