<?php

namespace Mosparo\Tests\Rule\Tester;

use Mosparo\Entity\Rule;
use Mosparo\Entity\RuleItem;
use Mosparo\Rule\Tester\ProviderRuleTester;

class ProviderRuleTesterTest extends TestCaseWithItems
{
    public function testValidateDataIpAddress()
    {
        $ruleStub = $this->createStub(Rule::class);
        $ruleStub
            ->method('getItems')
            ->willReturn($this->buildItemsCollection(RuleItem::class, [
                ['type' => 'asNumber', 'value' => '1234', 'rating' => 5.0]
            ]));

        $ruleTester = new ProviderRuleTester();
        $result = $ruleTester->validateData('asNumber', '1234', $ruleStub);

        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertEquals([['type' => 'asNumber', 'value' => '1234', 'rating' => 5.0, 'uuid' => null]], $result);
    }

    public function testValidateDataSubnet()
    {
        $ruleStub = $this->createStub(Rule::class);
        $ruleStub
            ->method('getItems')
            ->willReturn($this->buildItemsCollection(RuleItem::class, [
                ['type' => 'country', 'value' => 'CH', 'rating' => 5.0]
            ]));

        $ruleTester = new ProviderRuleTester();
        $result = $ruleTester->validateData('country', 'CH', $ruleStub);

        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertEquals([['type' => 'country', 'value' => 'CH', 'rating' => 5.0, 'uuid' => null]], $result);
    }

    public function testValidateDataNothingFound()
    {
        $ruleStub = $this->createStub(Rule::class);
        $ruleStub
            ->method('getItems')
            ->willReturn($this->buildItemsCollection(RuleItem::class, [
                ['type' => 'asNumber', 'value' => '1234', 'rating' => 5.0],
                ['type' => 'country', 'value' => 'CH', 'rating' => 5.0]
            ]));

        $ruleTester = new ProviderRuleTester();
        $result = $ruleTester->validateData('asNumber', '4321', $ruleStub);

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }
}
