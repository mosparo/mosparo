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
        $result = $ruleTester->validateData('test', 'word1', 'word1', $ruleItemStub);

        $this->assertIsArray($result);
        $this->assertEquals(['type' => 'uaText', 'value' => 'word', 'rating' => 5.0, 'uuid' => null], $result);
    }

    /**
     * @see https://github.com/mosparo/mosparo/issues/380
     */
    public function testValidateDataWordWithUppercase()
    {
        $ruleStub = $this->createStub(Rule::class);

        $ruleItemStub = $this->createStub(RuleItem::class);
        $ruleItemStub
            ->method('getType')
            ->willReturn('uaText');
        $ruleItemStub
            ->method('getValue')
            ->willReturn('Word');
        $ruleItemStub
            ->method('getSpamRatingFactor')
            ->willReturn(5.0);
        $ruleItemStub
            ->method('getParent')
            ->willReturn($ruleStub);

        $ruleTester = new UserAgentRuleTester();
        $result = $ruleTester->validateData('test', 'word1', 'Word1', $ruleItemStub);

        $this->assertIsArray($result);
        $this->assertEquals(['type' => 'uaText', 'value' => 'Word', 'rating' => 5.0, 'uuid' => null], $result);
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
        $result = $ruleTester->validateData('test', 'word1', 'word1', $ruleItemStub);

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
        $result = $ruleTester->validateData('test', 'word001', 'word001', $ruleItemStub);

        $this->assertIsArray($result);
        $this->assertEquals(['type' => 'uaRegex', 'value' => '(word\d+)', 'rating' => 5.0, 'uuid' => null], $result);
    }

    /**
     * @see https://github.com/mosparo/mosparo/issues/380
     */
    public function testValidateDataRegexWithUppercase()
    {
        $ruleStub = $this->createStub(Rule::class);

        $ruleItemStub = $this->createStub(RuleItem::class);
        $ruleItemStub
            ->method('getType')
            ->willReturn('uaRegex');
        $ruleItemStub
            ->method('getValue')
            ->willReturn('([A-Z]{3,}ord\d+)');
        $ruleItemStub
            ->method('getSpamRatingFactor')
            ->willReturn(5.0);
        $ruleItemStub
            ->method('getParent')
            ->willReturn($ruleStub);

        $ruleTester = new UserAgentRuleTester();
        $result = $ruleTester->validateData('test', 'wwword001', 'WWWord001', $ruleItemStub);

        $this->assertIsArray($result);
        $this->assertEquals(['type' => 'uaRegex', 'value' => '([A-Z]{3,}ord\d+)', 'rating' => 5.0, 'uuid' => null], $result);
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
        $result = $ruleTester->validateData('test', 'not-found', 'not-found', $ruleItemStub);

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }
}
