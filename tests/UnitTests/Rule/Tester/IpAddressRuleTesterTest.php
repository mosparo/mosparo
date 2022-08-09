<?php

namespace Mosparo\Tests\UnitTests\Rule\Tester;

use Mosparo\Entity\Rule;
use Mosparo\Entity\RuleItem;
use Mosparo\Rule\Tester\IpAddressRuleTester;

class IpAddressRuleTesterTest extends TestCaseWithItems
{
    public function testValidateDataIpAddress()
    {
        $ruleStub = $this->createStub(Rule::class);
        $ruleStub
            ->method('getItems')
            ->willReturn($this->buildItemsCollection(RuleItem::class, [
                ['type' => 'ipAddress', 'value' => '127.0.0.1', 'rating' => 5.0]
            ]));

        $ruleTester = new IpAddressRuleTester();
        $result = $ruleTester->validateData('test', '127.0.0.1', $ruleStub);

        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertEquals([['type' => 'ipAddress', 'value' => '127.0.0.1', 'rating' => 5.0, 'uuid' => null]], $result);
    }

    public function testValidateDataSubnet()
    {
        $ruleStub = $this->createStub(Rule::class);
        $ruleStub
            ->method('getItems')
            ->willReturn($this->buildItemsCollection(RuleItem::class, [
                ['type' => 'subnet', 'value' => '192.168.0.0/24', 'rating' => 5.0]
            ]));

        $ruleTester = new IpAddressRuleTester();
        $result = $ruleTester->validateData('test', '192.168.0.123', $ruleStub);

        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertEquals([['type' => 'subnet', 'value' => '192.168.0.0/24', 'rating' => 5.0, 'uuid' => null]], $result);
    }

    public function testValidateDataNothingFound()
    {
        $ruleStub = $this->createStub(Rule::class);
        $ruleStub
            ->method('getItems')
            ->willReturn($this->buildItemsCollection(RuleItem::class, [
                ['type' => 'ipAddress', 'value' => '127.0.0.1', 'rating' => 5.0],
                ['type' => 'subnet', 'value' => '192.168.0.0/24', 'rating' => 5.0]
            ]));

        $ruleTester = new IpAddressRuleTester();
        $result = $ruleTester->validateData('test', '192.168.1.123', $ruleStub);

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }
}
