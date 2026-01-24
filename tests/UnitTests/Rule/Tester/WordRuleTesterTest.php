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
        $result = $ruleTester->validateData('test', 'word1', 'word1', $ruleItemStub);

        $this->assertIsArray($result);
        $this->assertEquals(['type' => 'text', 'value' => 'word', 'rating' => 5.0, 'uuid' => null], $result);
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
            ->willReturn('text');
        $ruleItemStub
            ->method('getValue')
            ->willReturn('Word');
        $ruleItemStub
            ->method('getSpamRatingFactor')
            ->willReturn(5.0);
        $ruleItemStub
            ->method('getParent')
            ->willReturn($ruleStub);

        $ruleTester = new WordRuleTester();
        $result = $ruleTester->validateData('test', 'word1', 'Word1', $ruleItemStub);

        $this->assertIsArray($result);
        $this->assertEquals(['type' => 'text', 'value' => 'Word', 'rating' => 5.0, 'uuid' => null], $result);
    }

    /**
     * @see https://github.com/mosparo/mosparo/issues/380
     */
    public function testValidateDataWordWithZeroRating()
    {
        $ruleStub = $this->createStub(Rule::class);

        $ruleItemStub = $this->createStub(RuleItem::class);
        $ruleItemStub
            ->method('getType')
            ->willReturn('text');
        $ruleItemStub
            ->method('getValue')
            ->willReturn('Word');
        $ruleItemStub
            ->method('getSpamRatingFactor')
            ->willReturn(0.0);
        $ruleItemStub
            ->method('getParent')
            ->willReturn($ruleStub);

        $ruleTester = new WordRuleTester();
        $result = $ruleTester->validateData('test', 'word1', 'Word1', $ruleItemStub);

        $this->assertIsArray($result);
        $this->assertEquals(['type' => 'text', 'value' => 'Word', 'rating' => 0.0, 'uuid' => null], $result);
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
        $result = $ruleTester->validateData('test', 'word1', 'word1', $ruleItemStub);

        $this->assertIsArray($result);
        $this->assertEquals(['type' => 'text', 'value' => 'wo*', 'rating' => 5.0, 'uuid' => null], $result);
    }

    public function testValidateDataNoMatch()
    {
        $ruleStub = $this->createStub(Rule::class);

        $ruleItemStub = $this->createStub(RuleItem::class);
        $ruleItemStub
            ->method('getType')
            ->willReturn('text');
        $ruleItemStub
            ->method('getValue')
            ->willReturn('webserver');
        $ruleItemStub
            ->method('getSpamRatingFactor')
            ->willReturn(5.0);
        $ruleItemStub
            ->method('getParent')
            ->willReturn($ruleStub);

        $ruleTester = new WordRuleTester();
        $result = $ruleTester->validateData('test', 'the database', 'the database', $ruleItemStub);

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function testValidateDataExactWord()
    {
        $ruleStub = $this->createStub(Rule::class);

        $ruleItemStub = $this->createStub(RuleItem::class);
        $ruleItemStub
            ->method('getType')
            ->willReturn('wExact');
        $ruleItemStub
            ->method('getValue')
            ->willReturn('data');
        $ruleItemStub
            ->method('getSpamRatingFactor')
            ->willReturn(5.0);
        $ruleItemStub
            ->method('getParent')
            ->willReturn($ruleStub);

        $ruleTester = new WordRuleTester();
        $result = $ruleTester->validateData('test', 'the data in the database', 'the data in the database', $ruleItemStub);

        $this->assertIsArray($result);
        $this->assertEquals(['type' => 'wExact', 'value' => 'data', 'rating' => 5.0, 'uuid' => null], $result);
    }

    public function testValidateDataExactWordNotMatching()
    {
        $ruleStub = $this->createStub(Rule::class);

        $ruleItemStub = $this->createStub(RuleItem::class);
        $ruleItemStub
            ->method('getType')
            ->willReturn('wExact');
        $ruleItemStub
            ->method('getValue')
            ->willReturn('data');
        $ruleItemStub
            ->method('getSpamRatingFactor')
            ->willReturn(5.0);
        $ruleItemStub
            ->method('getParent')
            ->willReturn($ruleStub);

        $ruleTester = new WordRuleTester();
        $result = $ruleTester->validateData('test', 'the database', 'the database', $ruleItemStub);

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function testValidateDataFullMatch()
    {
        $ruleStub = $this->createStub(Rule::class);

        $ruleItemStub = $this->createStub(RuleItem::class);
        $ruleItemStub
            ->method('getType')
            ->willReturn('wFull');
        $ruleItemStub
            ->method('getValue')
            ->willReturn('the database');
        $ruleItemStub
            ->method('getSpamRatingFactor')
            ->willReturn(5.0);
        $ruleItemStub
            ->method('getParent')
            ->willReturn($ruleStub);

        $ruleTester = new WordRuleTester();
        // Value with trailing space
        $result = $ruleTester->validateData('test', 'the database ', 'the database ', $ruleItemStub);

        $this->assertIsArray($result);
        $this->assertEquals(['type' => 'wFull', 'value' => 'the database', 'rating' => 5.0, 'uuid' => null], $result);
    }

    public function testValidateDataFullNoMatch()
    {
        $ruleStub = $this->createStub(Rule::class);

        $ruleItemStub = $this->createStub(RuleItem::class);
        $ruleItemStub
            ->method('getType')
            ->willReturn('wFull');
        $ruleItemStub
            ->method('getValue')
            ->willReturn('the database');
        $ruleItemStub
            ->method('getSpamRatingFactor')
            ->willReturn(5.0);
        $ruleItemStub
            ->method('getParent')
            ->willReturn($ruleStub);

        $ruleTester = new WordRuleTester();
        $result = $ruleTester->validateData('test', 'the data in the database', 'the data in the database', $ruleItemStub);

        $this->assertIsArray($result);
        $this->assertEmpty($result);
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
        $result = $ruleTester->validateData('test', 'word001', 'word001', $ruleItemStub);

        $this->assertIsArray($result);
        $this->assertEquals(['type' => 'regex', 'value' => '(word\d+)', 'rating' => 5.0, 'uuid' => null], $result);
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
            ->willReturn('regex');
        $ruleItemStub
            ->method('getValue')
            ->willReturn('([A-Z]{3,}ord\d+)');
        $ruleItemStub
            ->method('getSpamRatingFactor')
            ->willReturn(5.0);
        $ruleItemStub
            ->method('getParent')
            ->willReturn($ruleStub);

        $ruleTester = new WordRuleTester();
        $result = $ruleTester->validateData('test', 'wwword001', 'WWWord001', $ruleItemStub);

        $this->assertIsArray($result);
        $this->assertEquals(['type' => 'regex', 'value' => '([A-Z]{3,}ord\d+)', 'rating' => 5.0, 'uuid' => null], $result);
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
        $result = $ruleTester->validateData('test', 'not-found', 'not-found', $ruleItemStub);

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }
}
