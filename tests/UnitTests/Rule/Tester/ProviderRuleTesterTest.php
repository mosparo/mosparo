<?php

namespace Mosparo\Tests\UnitTests\Rule\Tester;

use Mosparo\Entity\Rule;
use Mosparo\Entity\RuleItem;
use Mosparo\Rule\Tester\ProviderRuleTester;

class ProviderRuleTesterTest extends TestCaseWithItems
{
    public function testValidateDataIpAddress()
    {
        $ruleStub = $this->createStub(Rule::class);

        $ruleItemStub = $this->createStub(RuleItem::class);
        $ruleItemStub
            ->method('getType')
            ->willReturn('asNumber');
        $ruleItemStub
            ->method('getValue')
            ->willReturn('1234');
        $ruleItemStub
            ->method('getSpamRatingFactor')
            ->willReturn(5.0);
        $ruleItemStub
            ->method('getParent')
            ->willReturn($ruleStub);

        $ruleTester = new ProviderRuleTester();
        $result = $ruleTester->validateData('asNumber', '1234', $ruleItemStub);

        $this->assertIsArray($result);
        $this->assertEquals(['type' => 'asNumber', 'value' => '1234', 'rating' => 5.0, 'uuid' => null], $result);
    }

    public function testValidateDataSubnet()
    {
        $ruleStub = $this->createStub(Rule::class);

        $ruleItemStub = $this->createStub(RuleItem::class);
        $ruleItemStub
            ->method('getType')
            ->willReturn('country');
        $ruleItemStub
            ->method('getValue')
            ->willReturn('CH');
        $ruleItemStub
            ->method('getSpamRatingFactor')
            ->willReturn(5.0);
        $ruleItemStub
            ->method('getParent')
            ->willReturn($ruleStub);

        $ruleTester = new ProviderRuleTester();
        $result = $ruleTester->validateData('country', 'CH', $ruleItemStub);

        $this->assertIsArray($result);
        $this->assertEquals(['type' => 'country', 'value' => 'CH', 'rating' => 5.0, 'uuid' => null], $result);
    }

    public function testValidateDataNothingFound()
    {
        $ruleStub = $this->createStub(Rule::class);

        $ruleItemStub = $this->createStub(RuleItem::class);
        $ruleItemStub
            ->method('getType')
            ->willReturn('asNumber');
        $ruleItemStub
            ->method('getValue')
            ->willReturn('1234');
        $ruleItemStub
            ->method('getSpamRatingFactor')
            ->willReturn(5.0);
        $ruleItemStub
            ->method('getParent')
            ->willReturn($ruleStub);

        $ruleTester = new ProviderRuleTester();
        $result = $ruleTester->validateData('asNumber', '4321', $ruleItemStub);

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }
}
