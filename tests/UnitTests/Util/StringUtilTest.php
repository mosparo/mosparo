<?php

namespace Mosparo\Tests\UnitTests\Util;

use Mosparo\Util\StringUtil;
use PHPUnit\Framework\TestCase;

class StringUtilTest extends TestCase
{
    public function testObscureString()
    {
        $baseString = 'aaaa__aaaa';

        $this->assertEquals('aaaa**aaaa', StringUtil::obfuscateString($baseString));
    }
}
