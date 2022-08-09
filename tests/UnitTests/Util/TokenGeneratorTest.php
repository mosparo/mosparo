<?php

namespace Mosparo\Tests\UnitTests\Util;

use Mosparo\Util\TokenGenerator;
use PHPUnit\Framework\TestCase;

class TokenGeneratorTest extends TestCase
{
    public function testGenerateToken()
    {
        $tokenGenerator = new TokenGenerator();
        $token = $tokenGenerator->generateToken();

        $this->assertGreaterThan(32, strlen($token));

        $secondToken = $tokenGenerator->generateToken();
        $this->assertNotSame($token, $secondToken);
    }

    public function testGenerateShortToken()
    {
        $tokenGenerator = new TokenGenerator();
        $token = $tokenGenerator->generateShortToken();

        $this->assertGreaterThan(8, strlen($token));

        $secondToken = $tokenGenerator->generateShortToken();
        $this->assertNotSame($token, $secondToken);
    }
}
