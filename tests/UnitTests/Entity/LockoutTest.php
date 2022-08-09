<?php

namespace Mosparo\Tests\UnitTests\Entity;

use DateTime;
use Mosparo\Entity\Lockout;
use PHPUnit\Framework\TestCase;
use ReflectionObject;

class LockoutTest extends TestCase
{
    public function testGetId()
    {
        $lockout = new Lockout();

        $refObject = new ReflectionObject($lockout);
        $refProperty = $refObject->getProperty('id');
        $refProperty->setAccessible(true);
        $refProperty->setValue($lockout, '123');

        $this->assertEquals(123, $lockout->getId());
    }

    public function testGetIpAddress()
    {
        $lockout = new Lockout();
        $lockout->setIpAddress('127.0.0.1');

        $this->assertEquals('127.0.0.1', $lockout->getIpAddress());
    }

    public function testGetDuration()
    {
        $lockout = new Lockout();
        $lockout->setDuration(300);

        $this->assertEquals(300, $lockout->getDuration());
    }

    public function testGetValidUntil()
    {
        $dateTime = new DateTime('2099-01-01T23:59:59');

        $lockout = new Lockout();
        $lockout->setValidUntil($dateTime);

        $this->assertSame($dateTime, $lockout->getValidUntil());
    }

    public function testGetStartedAt()
    {
        $dateTime = new DateTime('2022-01-01T01:01:01');

        $lockout = new Lockout();
        $lockout->setStartedAt($dateTime);

        $this->assertSame($dateTime, $lockout->getStartedAt());
    }
}
