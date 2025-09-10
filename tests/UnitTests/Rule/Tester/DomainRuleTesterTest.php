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

        $ruleItemStub = $this->createStub(RuleItem::class);
        $ruleItemStub
            ->method('getType')
            ->willReturn('website');
        $ruleItemStub
            ->method('getValue')
            ->willReturn('example.com');
        $ruleItemStub
            ->method('getSpamRatingFactor')
            ->willReturn(5.0);
        $ruleItemStub
            ->method('getParent')
            ->willReturn($ruleStub);

        $ruleTester = new DomainRuleTester();
        $result = $ruleTester->validateData('test', 'https://test.example.com/test/test.html', $ruleItemStub);

        $this->assertIsArray($result);
        $this->assertEquals(['type' => 'website', 'value' => 'example.com', 'rating' => 5.0, 'uuid' => null], $result);
    }

    public function testValidateDataDomainInEmail()
    {
        $ruleStub = $this->createStub(Rule::class);

        $ruleItemStub = $this->createStub(RuleItem::class);
        $ruleItemStub
            ->method('getType')
            ->willReturn('website');
        $ruleItemStub
            ->method('getValue')
            ->willReturn('example.com');
        $ruleItemStub
            ->method('getSpamRatingFactor')
            ->willReturn(5.0);
        $ruleItemStub
            ->method('getParent')
            ->willReturn($ruleStub);

        $ruleTester = new DomainRuleTester();
        $result = $ruleTester->validateData('test', 'no-reply@example.com', $ruleItemStub);

        $this->assertIsArray($result);
        $this->assertEquals(['type' => 'website', 'value' => 'example.com', 'rating' => 5.0, 'uuid' => null], $result);
    }

    public function testValidateDataNothingFound()
    {
        $ruleStub = $this->createStub(Rule::class);

        $ruleItemStub = $this->createStub(RuleItem::class);
        $ruleItemStub
            ->method('getType')
            ->willReturn('website');
        $ruleItemStub
            ->method('getValue')
            ->willReturn('example.com');
        $ruleItemStub
            ->method('getSpamRatingFactor')
            ->willReturn(5.0);
        $ruleItemStub
            ->method('getParent')
            ->willReturn($ruleStub);

        $ruleTester = new DomainRuleTester();
        $result = $ruleTester->validateData('test', 'https://exam.pletest.com/test/test.html', $ruleItemStub);

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function testValidateDataNothingFoundExtendedTld()
    {
        $ruleStub = $this->createStub(Rule::class);

        $ruleItemStub = $this->createStub(RuleItem::class);
        $ruleItemStub
            ->method('getType')
            ->willReturn('website');
        $ruleItemStub
            ->method('getValue')
            ->willReturn('example.net');
        $ruleItemStub
            ->method('getSpamRatingFactor')
            ->willReturn(5.0);
        $ruleItemStub
            ->method('getParent')
            ->willReturn($ruleStub);

        $ruleTester = new DomainRuleTester();
        $result = $ruleTester->validateData('test', 'https://example.network/test/test.html', $ruleItemStub);

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function testValidateDataNothingFoundExtendedDomain()
    {
        $ruleStub = $this->createStub(Rule::class);

        $ruleItemStub = $this->createStub(RuleItem::class);
        $ruleItemStub
            ->method('getType')
            ->willReturn('website');
        $ruleItemStub
            ->method('getValue')
            ->willReturn('example.net');
        $ruleItemStub
            ->method('getSpamRatingFactor')
            ->willReturn(5.0);
        $ruleItemStub
            ->method('getParent')
            ->willReturn($ruleStub);

        $ruleTester = new DomainRuleTester();
        $result = $ruleTester->validateData('test', 'texample.net', $ruleItemStub);

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }
}
