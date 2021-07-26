<?php

namespace Mosparo\Tests\Rule\Tester;

use Mosparo\Entity\Rule;
use Mosparo\Entity\RuleItem;
use Mosparo\Entity\Ruleset;
use Mosparo\Entity\RulesetCache;
use Mosparo\Entity\RulesetRuleCache;
use Mosparo\Entity\RulesetRuleItemCache;
use Mosparo\Rule\Tester\WordRuleTester;

class AbstractRuleTesterTest extends TestCaseWithItems
{
    public function testValidateRuleSpamRatingFactor()
    {
        $ruleStub = $this->createStub(Rule::class);
        $ruleStub
            ->method('getItems')
            ->willReturn($this->buildItemsCollection(RuleItem::class, [
                ['type' => 'text', 'value' => 'word', 'rating' => 5.0]
            ]));
        $ruleStub
            ->method('getSpamRatingFactor')
            ->willReturn(2.0);

        $ruleTester = new WordRuleTester();
        $result = $ruleTester->validateData('test', 'word1', $ruleStub);

        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertEquals([['type' => 'text', 'value' => 'word', 'rating' => 10.0, 'uuid' => null]], $result);
    }

    public function testValidateDataRulesetSpamRating()
    {
        $rulesetStub = $this->createStub(Ruleset::class);
        $rulesetStub
            ->method('getSpamRatingFactor')
            ->willReturn(7.0);

        $rulesetCacheStub = $this->createStub(RulesetCache::class);
        $rulesetCacheStub
            ->method('getRuleset')
            ->willReturn($rulesetStub);

        $ruleCacheStub = $this->createStub(RulesetRuleCache::class);
        $ruleCacheStub
            ->method('getItems')
            ->willReturn($this->buildItemsCollection(RulesetRuleItemCache::class, [
                ['type' => 'text', 'value' => 'word', 'rating' => 5.0],
            ]));
        $ruleCacheStub
            ->method('getRulesetCache')
            ->willReturn($rulesetCacheStub);

        $ruleTester = new WordRuleTester();
        $result = $ruleTester->validateData('test', 'word', $ruleCacheStub);

        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertEquals([['type' => 'text', 'value' => 'word', 'rating' => 35.0, 'uuid' => null]], $result);
    }
}
