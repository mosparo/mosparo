<?php

namespace Mosparo\Tests\UnitTests\Rule\Tester;

use Mosparo\Entity\Rule;
use Mosparo\Entity\RuleItem;
use Mosparo\Rule\Tester\WordRuleTester;

class WordRuleTesterTest extends TestCaseWithItems
{
    public function testValidateDataWord()
    {
        $ruleStub = $this->createStub(Rule::class);

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
        $result = $ruleTester->validateData('test', 'word1', $ruleItemStub);

        $this->assertIsArray($result);
        $this->assertEquals(['type' => 'text', 'value' => 'word', 'rating' => 5.0, 'uuid' => null], $result);
    }

    public function testValidateDataWildcard()
    {
        $ruleStub = $this->createStub(Rule::class);

        $ruleItemStub = $this->createStub(RuleItem::class);
        $ruleItemStub
            ->method('getType')
            ->willReturn('text');
        $ruleItemStub
            ->method('getValue')
            ->willReturn('wo*');
        $ruleItemStub
            ->method('getSpamRatingFactor')
            ->willReturn(5.0);
        $ruleItemStub
            ->method('getParent')
            ->willReturn($ruleStub);

        $ruleTester = new WordRuleTester();
        $result = $ruleTester->validateData('test', 'word1', $ruleItemStub);

        $this->assertIsArray($result);
        $this->assertEquals(['type' => 'text', 'value' => 'wo*', 'rating' => 5.0, 'uuid' => null], $result);
    }

    public function testValidateDataRegex()
    {
        $ruleStub = $this->createStub(Rule::class);

        $ruleItemStub = $this->createStub(RuleItem::class);
        $ruleItemStub
            ->method('getType')
            ->willReturn('regex');
        $ruleItemStub
            ->method('getValue')
            ->willReturn('(word\d+)');
        $ruleItemStub
            ->method('getSpamRatingFactor')
            ->willReturn(5.0);
        $ruleItemStub
            ->method('getParent')
            ->willReturn($ruleStub);

        $ruleTester = new WordRuleTester();
        $result = $ruleTester->validateData('test', 'word001', $ruleItemStub);

        $this->assertIsArray($result);
        $this->assertEquals(['type' => 'regex', 'value' => '(word\d+)', 'rating' => 5.0, 'uuid' => null], $result);
    }

    public function testValidateDataNothingFound()
    {
        $ruleStub = $this->createStub(Rule::class);

        $ruleItemStub = $this->createStub(RuleItem::class);
        $ruleItemStub
            ->method('getType')
            ->willReturn('regex');
        $ruleItemStub
            ->method('getValue')
            ->willReturn('(word\d+)');
        $ruleItemStub
            ->method('getSpamRatingFactor')
            ->willReturn(5.0);
        $ruleItemStub
            ->method('getParent')
            ->willReturn($ruleStub);

        $ruleTester = new WordRuleTester();
        $result = $ruleTester->validateData('test', 'not-found', $ruleItemStub);

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }
}
