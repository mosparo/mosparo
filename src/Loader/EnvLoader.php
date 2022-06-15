<?php

namespace Mosparo\Loader;

use Mosparo\Helper\ConfigHelper;
use Symfony\Component\DependencyInjection\EnvVarLoaderInterface;

final class EnvLoader implements EnvVarLoaderInterface
{
    protected ConfigHelper $configHelper;

    public function __construct(ConfigHelper $configHelper)
    {
        $this->configHelper = $configHelper;
    }

    public function loadEnvVars(): array
    {
        $values = $this->configHelper->readEnvironmentConfig();

        if (isset($values['encryption_key'])) {
            $_ENV['ENCRYPTION_KEY'] = $values['encryption_key'];
            $_ENV['ENABLE_ENCRYPTION'] = true;
        }

        return $values;
    }
}