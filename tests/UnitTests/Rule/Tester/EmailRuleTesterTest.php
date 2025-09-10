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

        $ruleItemStub = $this->createStub(RuleItem::class);
        $ruleItemStub
            ->method('getType')
            ->willReturn('email');
        $ruleItemStub
            ->method('getValue')
            ->willReturn('no-reply@example.com');
        $ruleItemStub
            ->method('getSpamRatingFactor')
            ->willReturn(5.0);
        $ruleItemStub
            ->method('getParent')
            ->willReturn($ruleStub);

        $ruleTester = new EmailRuleTester();
        $result = $ruleTester->validateData('test', 'no-reply@example.com', $ruleItemStub);

        $this->assertIsArray($result);
        $this->assertEquals(['type' => 'email', 'value' => 'no-reply@example.com', 'rating' => 5.0, 'uuid' => null], $result);
    }

    public function testValidateDataEmailInText()
    {
        $ruleStub = $this->createStub(Rule::class);

        $ruleItemStub = $this->createStub(RuleItem::class);
        $ruleItemStub
            ->method('getType')
            ->willReturn('email');
        $ruleItemStub
            ->method('getValue')
            ->willReturn('no-reply@example.com');
        $ruleItemStub
            ->method('getSpamRatingFactor')
            ->willReturn(5.0);
        $ruleItemStub
            ->method('getParent')
            ->willReturn($ruleStub);

        $ruleTester = new EmailRuleTester();
        $result = $ruleTester->validateData('test', 'Test this is a no-reply@example.com text with email address in it.', $ruleItemStub);

        $this->assertIsArray($result);
        $this->assertEquals(['type' => 'email', 'value' => 'no-reply@example.com', 'rating' => 5.0, 'uuid' => null], $result);
    }

    public function testValidateDataNothingFound()
    {
        $ruleStub = $this->createStub(Rule::class);

        $ruleItemStub = $this->createStub(RuleItem::class);
        $ruleItemStub
            ->method('getType')
            ->willReturn('email');
        $ruleItemStub
            ->method('getValue')
            ->willReturn('no-reply@example.com');
        $ruleItemStub
            ->method('getSpamRatingFactor')
            ->willReturn(5.0);
        $ruleItemStub
            ->method('getParent')
            ->willReturn($ruleStub);

        $ruleTester = new EmailRuleTester();
        $result = $ruleTester->validateData('test', 'no-reply@test.com', $ruleItemStub);

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    /**
     * @see https://github.com/mosparo/mosparo/issues/132
     */
    public function testValidateDataDoNotFindPartialEmailAddressFullValue()
    {
        $ruleStub = $this->createStub(Rule::class);

        $ruleItemStub = $this->createStub(RuleItem::class);
        $ruleItemStub
            ->method('getType')
            ->willReturn('email');
        $ruleItemStub
            ->method('getValue')
            ->willReturn('no-reply@example.com');
        $ruleItemStub
            ->method('getSpamRatingFactor')
            ->willReturn(5.0);
        $ruleItemStub
            ->method('getParent')
            ->willReturn($ruleStub);

        $ruleTester = new EmailRuleTester();
        $result = $ruleTester->validateData('test', 'test+no-reply@test.com', $ruleItemStub);

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    /**
     * @see https://github.com/mosparo/mosparo/issues/132
     */
    public function testValidateDataDoNotFindPartialEmailAddressInText()
    {
        $ruleStub = $this->createStub(Rule::class);

        $ruleItemStub = $this->createStub(RuleItem::class);
        $ruleItemStub
            ->method('getType')
            ->willReturn('email');
        $ruleItemStub
            ->method('getValue')
            ->willReturn('no-reply@example.com');
        $ruleItemStub
            ->method('getSpamRatingFactor')
            ->willReturn(5.0);
        $ruleItemStub
            ->method('getParent')
            ->willReturn($ruleStub);

        $ruleTester = new EmailRuleTester();
        $result = $ruleTester->validateData('test', 'Test this is a test+no-reply@example.com text with email address in it.', $ruleItemStub);

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }
}
