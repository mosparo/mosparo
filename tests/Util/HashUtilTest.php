<?php

namespace Mosparo\Tests\Util;

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
}
