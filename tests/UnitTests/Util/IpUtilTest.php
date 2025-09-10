<?php

namespace Mosparo\Tests\UnitTests\Util;

use Mosparo\Util\IpUtil;
use PHPUnit\Framework\TestCase;

class IpUtilTest extends TestCase
{
    public function testIsIpAllowedEmptyAllowList()
    {
        $this->assertTrue(IpUtil::isIpAllowed('127.0.0.1', ''));
    }

    public function testIsIpAllowedAllowListWithIps()
    {
        $this->assertTrue(IpUtil::isIpAllowed('127.0.0.1', '127.0.0.1' . PHP_EOL . '192.168.0.1'));
    }

    public function testIsIpNotAllowedAllowListWithIps()
    {
        $this->assertFalse(IpUtil::isIpAllowed('127.0.0.2', '127.0.0.1' . PHP_EOL . '192.168.0.1'));
    }

    public function testIsIpAllowedAllowListWithSubnet()
    {
        $this->assertTrue(IpUtil::isIpAllowed('127.0.0.1', '127.0.0.0/24' . PHP_EOL . '192.168.0.0/24'));
    }

    public function testIsIpNotAllowedAllowListWithSubnet()
    {
        $this->assertFalse(IpUtil::isIpAllowed('127.0.1.1', '127.0.0.0/24' . PHP_EOL . '192.168.0.0/24'));
    }

    public function testConvertArrayToArray()
    {
        $allowList = ['127.0.0.1', '192.168.0.1'];

        $this->assertEquals($allowList, IpUtil::convertToArray($allowList));
    }

    public function testIsValidIpAddressEmpty()
    {
        $this->assertFalse(IpUtil::isValid(''));
    }

    public function testIsValidIpAddress()
    {
        $this->assertTrue(IpUtil::isValid('127.0.0.1'));
    }

    public function testIsValidSubnet()
    {
        $this->assertTrue(IpUtil::isValid('127.0.0.0/24'));
    }
}
