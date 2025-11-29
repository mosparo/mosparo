<?php

namespace Mosparo\Tests\UnitTests\Rule\Tester;

use Mosparo\Entity\Rule;
use Mosparo\Entity\RuleItem;
use Mosparo\Rule\Tester\IpAddressRuleTester;

class IpAddressRuleTesterTest extends TestCaseWithItems
{
    public function testValidateDataIpAddressV4()
    {
        $ruleStub = $this->createStub(Rule::class);

        $ruleItemStub = $this->createStub(RuleItem::class);
        $ruleItemStub
            ->method('getType')
            ->willReturn('ipAddress');
        $ruleItemStub
            ->method('getValue')
            ->willReturn('127.0.0.1');
        $ruleItemStub
            ->method('getSpamRatingFactor')
            ->willReturn(5.0);
        $ruleItemStub
            ->method('getParent')
            ->willReturn($ruleStub);

        $ruleTester = new IpAddressRuleTester();
        $result = $ruleTester->validateData('test', '127.0.0.1', '127.0.0.1', $ruleItemStub);

        $this->assertIsArray($result);
        $this->assertEquals(['type' => 'ipAddress', 'value' => '127.0.0.1', 'rating' => 5.0, 'uuid' => null], $result);
    }

    public function testValidateDataSubnetV4()
    {
        $ruleStub = $this->createStub(Rule::class);

        $ruleItemStub = $this->createStub(RuleItem::class);
        $ruleItemStub
            ->method('getType')
            ->willReturn('subnet');
        $ruleItemStub
            ->method('getValue')
            ->willReturn('192.168.0.0/24');
        $ruleItemStub
            ->method('getSpamRatingFactor')
            ->willReturn(5.0);
        $ruleItemStub
            ->method('getParent')
            ->willReturn($ruleStub);

        $ruleTester = new IpAddressRuleTester();
        $result = $ruleTester->validateData('test', '192.168.0.123', '192.168.0.123', $ruleItemStub);

        $this->assertIsArray($result);
        $this->assertEquals(['type' => 'subnet', 'value' => '192.168.0.0/24', 'rating' => 5.0, 'uuid' => null], $result);
    }

    /**
     * With uppercase characters, see https://github.com/mosparo/mosparo/issues/380
     */
    public function testValidateDataIpAddressV6()
    {
        $ruleStub = $this->createStub(Rule::class);

        $ruleItemStub = $this->createStub(RuleItem::class);
        $ruleItemStub
            ->method('getType')
            ->willReturn('ipAddress');
        $ruleItemStub
            ->method('getValue')
            ->willReturn('aaaa:BBBB:cccc:dddd::EEEE:ffff');
        $ruleItemStub
            ->method('getSpamRatingFactor')
            ->willReturn(5.0);
        $ruleItemStub
            ->method('getParent')
            ->willReturn($ruleStub);

        $ruleTester = new IpAddressRuleTester();
        $result = $ruleTester->validateData('test', 'aaaa:bbbb:cccc:dddd::eeee:ffff', 'aaaa:bbbb:CCCC:dddd::EEEE:FFFF', $ruleItemStub);

        $this->assertIsArray($result);
        $this->assertEquals(['type' => 'ipAddress', 'value' => 'aaaa:BBBB:cccc:dddd::EEEE:ffff', 'rating' => 5.0, 'uuid' => null], $result);
    }

    /**
     * With uppercase characters, see https://github.com/mosparo/mosparo/issues/380
     */
    public function testValidateDataSubnetV6()
    {
        $ruleStub = $this->createStub(Rule::class);

        $ruleItemStub = $this->createStub(RuleItem::class);
        $ruleItemStub
            ->method('getType')
            ->willReturn('subnet');
        $ruleItemStub
            ->method('getValue')
            ->willReturn('aaaa:bbbb:cccc:DDDD::/64');
        $ruleItemStub
            ->method('getSpamRatingFactor')
            ->willReturn(5.0);
        $ruleItemStub
            ->method('getParent')
            ->willReturn($ruleStub);

        $ruleTester = new IpAddressRuleTester();
        $result = $ruleTester->validateData('test', 'aaaa:bbbb:cccc:dddd::eeee:ffff', 'aaaa:bbbb:CCCC:dddd::EEEE:FFFF', $ruleItemStub);

        $this->assertIsArray($result);
        $this->assertEquals(['type' => 'subnet', 'value' => 'aaaa:bbbb:cccc:DDDD::/64', 'rating' => 5.0, 'uuid' => null], $result);
    }

    public function testValidateDataNothingFound()
    {
        $ruleStub = $this->createStub(Rule::class);

        $ruleItemStub = $this->createStub(RuleItem::class);
        $ruleItemStub
            ->method('getType')
            ->willReturn('ipAddress');
        $ruleItemStub
            ->method('getValue')
            ->willReturn('127.0.0.1');
        $ruleItemStub
            ->method('getSpamRatingFactor')
            ->willReturn(5.0);
        $ruleItemStub
            ->method('getParent')
            ->willReturn($ruleStub);

        $ruleTester = new IpAddressRuleTester();
        $result = $ruleTester->validateData('test', '192.168.1.123', '192.168.1.123', $ruleItemStub);

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }
}
