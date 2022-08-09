<?php

namespace Mosparo\Tests\UnitTests\Entity;

use DateTime;
use Mosparo\Entity\ResetPasswordRequest;
use Mosparo\Entity\User;
use PHPUnit\Framework\TestCase;
use ReflectionObject;

class ResetPasswordRequestTest extends TestCase
{
    public function testGetId()
    {
        $user = new User();
        $expires = new DateTime('2099-01-01T23:59:59');
        $resetPasswordRequest = new ResetPasswordRequest($user, $expires, 'test', 'test123');

        $refObject = new ReflectionObject($resetPasswordRequest);
        $refProperty = $refObject->getProperty('id');
        $refProperty->setAccessible(true);
        $refProperty->setValue($resetPasswordRequest, '123');

        $this->assertEquals(123, $resetPasswordRequest->getId());
        $this->assertSame($user, $resetPasswordRequest->getUser());
    }
}
