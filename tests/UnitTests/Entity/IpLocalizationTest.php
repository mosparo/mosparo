<?php

namespace Mosparo\Tests\UnitTests\Entity;

use DateTime;
use Mosparo\Entity\IpLocalization;
use PHPUnit\Framework\TestCase;
use ReflectionObject;

class IpLocalizationTest extends TestCase
{
    public function testGetId()
    {
        $ipLocalization = new IpLocalization();

        $refObject = new ReflectionObject($ipLocalization);
        $refProperty = $refObject->getProperty('id');
        $refProperty->setAccessible(true);
        $refProperty->setValue($ipLocalization, '123');

        $this->assertEquals(123, $ipLocalization->getId());
    }

    public function testGetIpAddress()
    {
        $ipLocalization = new IpLocalization();
        $ipLocalization->setIpAddress('127.0.0.1');

        $this->assertEquals('127.0.0.1', $ipLocalization->getIpAddress());
    }

    public function testGetAsNumber()
    {
        $ipLocalization = new IpLocalization();
        $ipLocalization->setAsNumber(123456);

        $this->assertEquals(123456, $ipLocalization->getAsNumber());
    }

    public function testGetAsOrganization()
    {
        $ipLocalization = new IpLocalization();
        $ipLocalization->setAsOrganization('Example Inc.');

        $this->assertEquals('Example Inc.', $ipLocalization->getAsOrganization());
    }

    public function testGetCountry()
    {
        $ipLocalization = new IpLocalization();
        $ipLocalization->setCountry('CH');

        $this->assertEquals('CH', $ipLocalization->getCountry());
    }

    public function testGetCachedAt()
    {
        $dateTime = new DateTime('2022-01-01T01:01:01');

        $ipLocalization = new IpLocalization();
        $ipLocalization->setCachedAt($dateTime);

        $this->assertSame($dateTime, $ipLocalization->getCachedAt());
    }
}
