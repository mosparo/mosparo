<?php

namespace Mosparo\Tests\UnitTests\Rule\Tester;

use Mosparo\Entity\Rule;
use Mosparo\Entity\RuleItem;
use Mosparo\Entity\RulePackage;
use Mosparo\Entity\RulePackageCache;
use Mosparo\Entity\RulePackageRuleCache;
use Mosparo\Entity\RulePackageRuleItemCache;
use Mosparo\Rule\Tester\WordRuleTester;

class AbstractRuleTesterTest extends TestCaseWithItems
{
    public function testValidateRuleSpamRatingFactor()
    {
        $ruleStub = $this->createStub(Rule::class);
        $ruleStub
            ->method('getSpamRatingFactor')
            ->willReturn(2.0);

        $ruleItemStub = $this->createStub(RuleItem::class);
        $ruleItemStub
            ->method('getType')
            ->willReturn('text');
        $ruleItemStub
            ->method('getValue')
            ->willReturn('word');
        $ruleItemStub
            ->method('getSpamRatingFactor')
            ->willReturn(5.0);
        $ruleItemStub
            ->method('getParent')
            ->willReturn($ruleStub);

        $ruleTester = new WordRuleTester();
        $result = $ruleTester->validateData('test', 'word1', 'word1', $ruleItemStub);

        $this->assertIsArray($result);
        $this->assertEquals(['type' => 'text', 'value' => 'word', 'rating' => 10.0, 'uuid' => null], $result);
    }

    public function testValidateDataRulePackageSpamRating()
    {
        $rulePackageStub = $this->createStub(RulePackage::class);
        $rulePackageStub
            ->method('getSpamRatingFactor')
            ->willReturn(7.0);

        $rulePackageCacheStub = $this->createStub(RulePackageCache::class);
        $rulePackageCacheStub
            ->method('getRulePackage')
            ->willReturn($rulePackageStub);

        $ruleCacheStub = $this->createStub(RulePackageRuleCache::class);
        $ruleCacheStub
            ->method('getItems')
            ->willReturn($this->buildItemsCollection(RulePackageRuleItemCache::class, [
                ['type' => 'text', 'value' => 'word', 'rating' => 5.0],
            ]));
        $ruleCacheStub
            ->method('getRulePackageCache')
            ->willReturn($rulePackageCacheStub);

        $ruleItemStub = $this->createStub(RulePackageRuleItemCache::class);
        $ruleItemStub
            ->method('getType')
            ->willReturn('text');
        $ruleItemStub
            ->method('getValue')
            ->willReturn('word');
        $ruleItemStub
            ->method('getSpamRatingFactor')
            ->willReturn(5.0);
        $ruleItemStub
            ->method('getParent')
            ->willReturn($ruleCacheStub);

        $ruleTester = new WordRuleTester();
        $result = $ruleTester->validateData('test', 'word', 'word', $ruleItemStub);

        $this->assertIsArray($result);
        $this->assertEquals(['type' => 'text', 'value' => 'word', 'rating' => 35.0, 'uuid' => null], $result);
    }
}
