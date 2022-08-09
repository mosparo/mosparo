<?php

namespace Mosparo\Tests\UnitTests\Loader;

use Mosparo\Helper\ConfigHelper;
use Mosparo\Loader\EnvLoader;
use PHPUnit\Framework\TestCase;

class EnvLoaderTest extends TestCase
{
    public function testLoadEnvVars()
    {
        $data = ['testKey' => 1234];

        $configHelperStub = $this->createMock(ConfigHelper::class);
        $configHelperStub
            ->expects($this->once())
            ->method('readEnvironmentConfig')
            ->willReturn($data);

        $envLoader = new EnvLoader($configHelperStub);

        $this->assertEquals($data, $envLoader->loadEnvVars());
    }

    public function testLoadEnvVarsEncryption()
    {
        $data = ['testKey' => 1234, 'encryption_key' => 'test123'];

        $configHelperStub = $this->createMock(ConfigHelper::class);
        $configHelperStub
            ->expects($this->once())
            ->method('readEnvironmentConfig')
            ->willReturn($data);

        $envLoader = new EnvLoader($configHelperStub);

        $this->assertEquals($data, $envLoader->loadEnvVars());
        $this->assertEquals($_ENV['ENCRYPTION_KEY'], 'test123');
        $this->assertTrue($_ENV['ENABLE_ENCRYPTION']);
    }
}
