<?php

namespace Mosparo\Tests\UnitTests\Util;

use Mosparo\Util\EnvironmentUtil;
use PHPUnit\Framework\TestCase;

class EnvironmentUtilTest extends TestCase
{
    public function testGetMemoryLimitInBytesUnlimited()
    {
        ini_set('memory_limit', -1);

        $this->assertEquals(256 * 1024 * 1024, EnvironmentUtil::getMemoryLimitInBytes());
    }

    public function testGetMemoryLimitInBytes1G()
    {
        ini_set('memory_limit', '1G');

        $this->assertEquals(1024 * 1024 * 1024, EnvironmentUtil::getMemoryLimitInBytes());
    }
}
