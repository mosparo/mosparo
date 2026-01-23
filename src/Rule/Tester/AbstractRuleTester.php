<?php

namespace Mosparo\Rule\Tester;

use Mosparo\Entity\RulePackageRuleCache;
use Mosparo\Rule\RuleItemEntityInterface;

abstract class AbstractRuleTester implements RuleTesterInterface
{
    protected function calculateSpamRating(RuleItemEntityInterface $item, $additionalFactor = 1): float
    {
        $rule = $item->getParent();
        $rating = 1;
        if ($item->getSpamRatingFactor() !== null) {
            $rating = floatval($item->getSpamRatingFactor());
        }

        $ruleSpamRatingFactor = 1;
        if ($rule->getSpamRatingFactor() !== null) {
            $ruleSpamRatingFactor = floatval($rule->getSpamRatingFactor());
        }

        $rating = ($rating * $additionalFactor) * $ruleSpamRatingFactor;

        if ($rule instanceof RulePackageRuleCache && !empty($rule->getRulePackageCache()->getRulePackage()->getSpamRatingFactor())) {
            $rating = $rating * floatval($rule->getRulePackageCache()->getRulePackage()->getSpamRatingFactor());
        }

        return $rating;
    }
}