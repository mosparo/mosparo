<?php

namespace Mosparo\Tests\UnitTests\Util;

use Mosparo\Util\HashUtil;
use PHPUnit\Framework\TestCase;

class HashUtilTest extends TestCase
{
    public function testHash()
    {
        $randomString = uniqid();
        $hash = HashUtil::hash($randomString);

        $this->assertEquals(hash('whirlpool', $randomString), $hash);
    }

    public function testSha256()
    {
        $randomString = uniqid();
        $hash = HashUtil::sha256Hash($randomString);

        $this->assertEquals(hash('sha256', $randomString), $hash);
    }

    public function testFast()
    {
        $randomString = uniqid();
        $hash = HashUtil::hashFast($randomString);

        $this->assertEquals(hash('xxh128', $randomString), $hash);
    }
}
