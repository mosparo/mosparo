<?php

namespace Mosparo\Tests\UnitTests\Rule\Tester;

use Mosparo\Entity\Rule;
use Mosparo\Entity\RuleItem;
use Mosparo\Rule\Tester\UserAgentRuleTester;

class UserAgentRuleTesterTest extends TestCaseWithItems
{
    public function testValidateDataWord()
    {
        $ruleStub = $this->createStub(Rule::class);

        $ruleItemStub = $this->createStub(RuleItem::class);
        $ruleItemStub
            ->method('getType')
            ->willReturn('uaText');
        $ruleItemStub
            ->method('getValue')
            ->willReturn('word');
        $ruleItemStub
            ->method('getSpamRatingFactor')
            ->willReturn(5.0);
        $ruleItemStub
            ->method('getParent')
            ->willReturn($ruleStub);

        $ruleTester = new UserAgentRuleTester();
        $result = $ruleTester->validateData('test', 'word1', $ruleItemStub);

        $this->assertIsArray($result);
        $this->assertEquals(['type' => 'uaText', 'value' => 'word', 'rating' => 5.0, 'uuid' => null], $result);
    }

    public function testValidateDataWildcard()
    {
        $ruleStub = $this->createStub(Rule::class);

        $ruleItemStub = $this->createStub(RuleItem::class);
        $ruleItemStub
            ->method('getType')
            ->willReturn('uaText');
        $ruleItemStub
            ->method('getValue')
            ->willReturn('wo*');
        $ruleItemStub
            ->method('getSpamRatingFactor')
            ->willReturn(5.0);
        $ruleItemStub
            ->method('getParent')
            ->willReturn($ruleStub);

        $ruleTester = new UserAgentRuleTester();
        $result = $ruleTester->validateData('test', 'word1', $ruleItemStub);

        $this->assertIsArray($result);
        $this->assertEquals(['type' => 'uaText', 'value' => 'wo*', 'rating' => 5.0, 'uuid' => null], $result);
    }

    public function testValidateDataRegex()
    {
        $ruleStub = $this->createStub(Rule::class);

        $ruleItemStub = $this->createStub(RuleItem::class);
        $ruleItemStub
            ->method('getType')
            ->willReturn('uaRegex');
        $ruleItemStub
            ->method('getValue')
            ->willReturn('(word\d+)');
        $ruleItemStub
            ->method('getSpamRatingFactor')
            ->willReturn(5.0);
        $ruleItemStub
            ->method('getParent')
            ->willReturn($ruleStub);

        $ruleTester = new UserAgentRuleTester();
        $result = $ruleTester->validateData('test', 'word001', $ruleItemStub);

        $this->assertIsArray($result);
        $this->assertEquals(['type' => 'uaRegex', 'value' => '(word\d+)', 'rating' => 5.0, 'uuid' => null], $result);
    }

    public function testValidateDataNothingFound()
    {
        $ruleStub = $this->createStub(Rule::class);

        $ruleItemStub = $this->createStub(RuleItem::class);
        $ruleItemStub
            ->method('getType')
            ->willReturn('uaRegex');
        $ruleItemStub
            ->method('getValue')
            ->willReturn('(word\d+)');
        $ruleItemStub
            ->method('getSpamRatingFactor')
            ->willReturn(5.0);
        $ruleItemStub
            ->method('getParent')
            ->willReturn($ruleStub);

        $ruleTester = new UserAgentRuleTester();
        $result = $ruleTester->validateData('test', 'not-found', $ruleItemStub);

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }
}
