<?php

namespace Mosparo\Rule\Tester;

use Mosparo\Entity\Rule;

abstract class AbstractRuleTester implements RuleTesterInterface
{
    protected function calculateSpamRating(Rule $rule, $item, $additionalFactor = 1): float
    {
        $rating = 1;
        if (isset($item['rating']) && $item['rating'] > 0) {
            $value = $item['rating'];
        }

        $rating = ($rating * $additionalFactor) * $rule->getSpamRatingFactor();

        return $rating;
    }
}