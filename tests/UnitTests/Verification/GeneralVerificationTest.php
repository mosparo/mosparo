<?php

namespace Mosparo\Tests\UnitTests\Verification;

use Mosparo\Verification\GeneralVerification;
use PHPUnit\Framework\TestCase;

class GeneralVerificationTest extends TestCase
{
    public function testGeneralVerification()
    {
        $data = ['test' => 123];
        $generalVerification = new GeneralVerification('testKey', true, $data);

        $this->assertEquals('testKey', $generalVerification->getKey());
        $this->assertTrue($generalVerification->isValid());
        $this->assertEquals($data, $generalVerification->getData());
    }

    public function testGeneralVerificationSetter()
    {
        $data = ['test' => 123];
        $generalVerification = new GeneralVerification('testKey', true, $data);

        // Adjust the values
        $data = ['abc' => 'test'];
        $generalVerification->setKey('keyTest');
        $generalVerification->setValid(false);
        $generalVerification->setData($data);

        $this->assertEquals('keyTest', $generalVerification->getKey());
        $this->assertFalse($generalVerification->isValid());
        $this->assertEquals($data, $generalVerification->getData());
    }
}
