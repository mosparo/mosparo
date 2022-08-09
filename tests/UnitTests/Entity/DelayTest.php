<?php

namespace Mosparo\Tests\UnitTests\Entity;

use DateTime;
use Mosparo\Entity\Delay;
use PHPUnit\Framework\TestCase;
use ReflectionObject;

class DelayTest extends TestCase
{
    public function testGetId()
    {
        $delay = new Delay();

        $refObject = new ReflectionObject($delay);
        $refProperty = $refObject->getProperty('id');
        $refProperty->setAccessible(true);
        $refProperty->setValue($delay, '123');

        $this->assertEquals(123, $delay->getId());
    }

    public function testGetIpAddress()
    {
        $delay = new Delay();
        $delay->setIpAddress('127.0.0.1');

        $this->assertEquals('127.0.0.1', $delay->getIpAddress());
    }

    public function testGetDuration()
    {
        $delay = new Delay();
        $delay->setDuration(300);

        $this->assertEquals(300, $delay->getDuration());
    }

    public function testGetValidUntil()
    {
        $dateTime = new DateTime('2099-01-01T23:59:59');

        $delay = new Delay();
        $delay->setValidUntil($dateTime);

        $this->assertSame($dateTime, $delay->getValidUntil());
    }

    public function testGetStartedAt()
    {
        $dateTime = new DateTime('2022-01-01T01:01:01');

        $delay = new Delay();
        $delay->setStartedAt($dateTime);

        $this->assertSame($dateTime, $delay->getStartedAt());
    }
}
