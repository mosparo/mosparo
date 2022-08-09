<?php

namespace Mosparo\Tests\UnitTests\Util;

use Mosparo\Util\TimeUtil;
use PHPUnit\Framework\TestCase;

class TimeUtilTest extends TestCase
{
    public function testGetDifferenceInSeconds()
    {
        $dateOne = new \DateTime('2022-01-01T00:00:00');
        $dateTwo = new \DateTime('2022-01-01T01:01:01');

        $diff = TimeUtil::getDifferenceInSeconds($dateOne, $dateTwo);

        $this->assertEquals(3661, $diff);
    }
}
