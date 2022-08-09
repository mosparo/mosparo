<?php

namespace Mosparo\Tests\UnitTests\Util;

use Mosparo\Util\ChoicesUtil;
use PHPUnit\Framework\TestCase;

class ChoicesUtilTest extends TestCase
{
    public function testBuildChoices()
    {
        $items = [
            [
                'name' => 'test',
                'key' => 1
            ],
            [
                'name' => 'test 2',
                'key' => 2
            ]
        ];
        $choices = ChoicesUtil::buildChoices($items);

        $target = [
            'test' => 1,
            'test 2' => 2
        ];

        $this->assertEquals($target, $choices);
    }
}
