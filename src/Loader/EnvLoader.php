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

        // The loader expects a string, not a boolean value. A real false will be read as 'not defined', which
        // means that it could fall back to the default value.
        foreach ($values as $key => $value) {
            if ($value === true) {
                $values[$key] = '1';
            } else if ($value === false) {
                $values[$key] = '0';
            }
        }

        return $values;
    }
}
