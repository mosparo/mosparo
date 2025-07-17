<?php

namespace Mosparo\Tests\UnitTests\Util;

use Mosparo\Util\PathUtil;
use PHPUnit\Framework\TestCase;

class PathUtilTest extends TestCase
{
    public function testPrepareFilePathUnix()
    {
        $baseString = '/var/www/test';

        // Test for unix file paths
        $this->assertEquals($baseString, PathUtil::prepareFilePath($baseString, false, '/'));
    }

    public function testPrepareFilePathWindows()
    {
        $baseString = 'C:/HTdocs/www/Test';

        // Test for unix file paths
        $this->assertEquals('C:\\HTdocs\\www\\Test', PathUtil::prepareFilePath($baseString, false, '\\'));
    }

    public function testPrepareFilePathWindowsLowerCase()
    {
        $baseString = 'C:/HTdocs/www/Test';

        // Test for unix file paths
        $this->assertEquals('c:\\htdocs\\www\\test', PathUtil::prepareFilePath($baseString, true, '\\'));
    }
}
