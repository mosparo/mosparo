<?php

namespace Mosparo\Tests\UnitTests\Rule\Tester;

use Mosparo\Entity\Rule;
use Mosparo\Entity\RuleItem;
use Mosparo\Rule\Tester\DomainRuleTester;

class DomainRuleTesterTest extends TestCaseWithItems
{
    public function testValidateDataDomain()
    {
        $ruleStub = $this->createStub(Rule::class);
        $ruleStub
            ->method('getItems')
            ->willReturn($this->buildItemsCollection(RuleItem::class, [
                ['type' => 'website', 'value' => 'example.com', 'rating' => 5.0]
            ]));

        $ruleTester = new DomainRuleTester();
        $result = $ruleTester->validateData('test', 'https://test.example.com/test/test.html', $ruleStub);

        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertEquals([['type' => 'website', 'value' => 'example.com', 'rating' => 5.0, 'uuid' => null]], $result);
    }

    public function testValidateDataDomainInEmail()
    {
        $ruleStub = $this->createStub(Rule::class);
        $ruleStub
            ->method('getItems')
            ->willReturn($this->buildItemsCollection(RuleItem::class, [
                ['type' => 'website', 'value' => 'example.com', 'rating' => 5.0]
            ]));

        $ruleTester = new DomainRuleTester();
        $result = $ruleTester->validateData('test', 'no-reply@example.com', $ruleStub);

        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertEquals([['type' => 'website', 'value' => 'example.com', 'rating' => 5.0, 'uuid' => null]], $result);
    }

    public function testValidateDataNothingFound()
    {
        $ruleStub = $this->createStub(Rule::class);
        $ruleStub
            ->method('getItems')
            ->willReturn($this->buildItemsCollection(RuleItem::class, [
                ['type' => 'website', 'value' => 'example.com', 'rating' => 5.0],
            ]));

        $ruleTester = new DomainRuleTester();
        $result = $ruleTester->validateData('test', 'https://exam.pletest.com/test/test.html', $ruleStub);

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function testValidateDataNothingFoundExtendedTld()
    {
        $ruleStub = $this->createStub(Rule::class);
        $ruleStub
            ->method('getItems')
            ->willReturn($this->buildItemsCollection(RuleItem::class, [
                ['type' => 'website', 'value' => 'example.net', 'rating' => 5.0],
            ]));

        $ruleTester = new DomainRuleTester();
        $result = $ruleTester->validateData('test', 'https://example.network/test/test.html', $ruleStub);

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function testValidateDataNothingFoundExtendedDomain()
    {
        $ruleStub = $this->createStub(Rule::class);
        $ruleStub
            ->method('getItems')
            ->willReturn($this->buildItemsCollection(RuleItem::class, [
                ['type' => 'website', 'value' => 'example.net', 'rating' => 5.0],
            ]));

        $ruleTester = new DomainRuleTester();
        $result = $ruleTester->validateData('test', 'texample.net', $ruleStub);

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }
}
