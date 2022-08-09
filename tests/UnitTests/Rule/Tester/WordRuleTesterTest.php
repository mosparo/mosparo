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
        $ruleStub
            ->method('getItems')
            ->willReturn($this->buildItemsCollection(RuleItem::class, [
                ['type' => 'text', 'value' => 'word', 'rating' => 5.0]
            ]));

        $ruleTester = new WordRuleTester();
        $result = $ruleTester->validateData('test', 'word1', $ruleStub);

        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertEquals([['type' => 'text', 'value' => 'word', 'rating' => 5.0, 'uuid' => null]], $result);
    }

    public function testValidateDataWildcard()
    {
        $ruleStub = $this->createStub(Rule::class);
        $ruleStub
            ->method('getItems')
            ->willReturn($this->buildItemsCollection(RuleItem::class, [
                ['type' => 'text', 'value' => 'wo*', 'rating' => 5.0]
            ]));

        $ruleTester = new WordRuleTester();
        $result = $ruleTester->validateData('test', 'word1', $ruleStub);

        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertEquals([['type' => 'text', 'value' => 'wo*', 'rating' => 5.0, 'uuid' => null]], $result);
    }

    public function testValidateDataRegex()
    {
        $ruleStub = $this->createStub(Rule::class);
        $ruleStub
            ->method('getItems')
            ->willReturn($this->buildItemsCollection(RuleItem::class, [
                ['type' => 'regex', 'value' => '(word\d+)', 'rating' => 5.0]
            ]));

        $ruleTester = new WordRuleTester();
        $result = $ruleTester->validateData('test', 'word001', $ruleStub);

        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertEquals([['type' => 'regex', 'value' => '(word\d+)', 'rating' => 5.0, 'uuid' => null]], $result);
    }

    public function testValidateDataNothingFound()
    {
        $ruleStub = $this->createStub(Rule::class);
        $ruleStub
            ->method('getItems')
            ->willReturn($this->buildItemsCollection(RuleItem::class, [
                ['type' => 'text', 'value' => 'word', 'rating' => 5.0],
                ['type' => 'regex', 'value' => '(word\d+)', 'rating' => 5.0]
            ]));

        $ruleTester = new WordRuleTester();
        $result = $ruleTester->validateData('test', 'not-found', $ruleStub);

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }
}
