<?php

namespace Mosparo\Tests\UnitTests\Rule\Tester;

use Mosparo\Entity\Rule;
use Mosparo\Entity\RuleItem;
use Mosparo\Rule\Tester\EmailRuleTester;

class EmailRuleTesterTest extends TestCaseWithItems
{
    public function testValidateDataEmail()
    {
        $ruleStub = $this->createStub(Rule::class);
        $ruleStub
            ->method('getItems')
            ->willReturn($this->buildItemsCollection(RuleItem::class, [
                ['type' => 'website', 'value' => 'no-reply@example.com', 'rating' => 5.0]
            ]));

        $ruleTester = new EmailRuleTester();
        $result = $ruleTester->validateData('test', 'no-reply@example.com', $ruleStub);

        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertEquals([['type' => 'website', 'value' => 'no-reply@example.com', 'rating' => 5.0, 'uuid' => null]], $result);
    }

    public function testValidateDataEmailInText()
    {
        $ruleStub = $this->createStub(Rule::class);
        $ruleStub
            ->method('getItems')
            ->willReturn($this->buildItemsCollection(RuleItem::class, [
                ['type' => 'website', 'value' => 'no-reply@example.com', 'rating' => 5.0]
            ]));

        $ruleTester = new EmailRuleTester();
        $result = $ruleTester->validateData('test', 'Test this is a no-reply@example.com text with email address in it.', $ruleStub);

        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertEquals([['type' => 'website', 'value' => 'no-reply@example.com', 'rating' => 5.0, 'uuid' => null]], $result);
    }

    public function testValidateDataNothingFound()
    {
        $ruleStub = $this->createStub(Rule::class);
        $ruleStub
            ->method('getItems')
            ->willReturn($this->buildItemsCollection(RuleItem::class, [
                ['type' => 'website', 'value' => 'no-reply@example.com', 'rating' => 5.0],
            ]));

        $ruleTester = new EmailRuleTester();
        $result = $ruleTester->validateData('test', 'no-reply@test.com', $ruleStub);

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }
}
