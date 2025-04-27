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

        $ruleTester = new WordRuleTester();
        $result = $ruleTester->validateData('test', 'word', $ruleCacheStub);

        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertEquals([['type' => 'text', 'value' => 'word', 'rating' => 35.0, 'uuid' => null]], $result);
    }
}
