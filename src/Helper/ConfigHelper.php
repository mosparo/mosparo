<?php

namespace Mosparo\Helper;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Filesystem\Filesystem;

class ConfigHelper
{
    protected EntityManagerInterface $entityManager;

    protected Filesystem $fileSystem;

    protected string $environmentConfigFilePath;

    protected array $environmentConfigValues = [];

    public function __construct(EntityManagerInterface $entityManager, Filesystem $fileSystem, string $projectDirectory)
    {
        $this->entityManager = $entityManager;
        $this->fileSystem = $fileSystem;
        $this->environmentConfigFilePath = $projectDirectory . '/config/env.mosparo.php';

        if (is_link($this->environmentConfigFilePath)) {
            $realConfigFilePath = realpath($this->environmentConfigFilePath);

            if ($realConfigFilePath) {
                $this->environmentConfigFilePath = $realConfigFilePath;
            }
        }
    }

    public function getEnvironmentConfigValue($name, $defaultValue = false)
    {
        if (!$this->environmentConfigValues) {
            $this->environmentConfigValues = $this->readEnvironmentConfig();
        }

        if (!isset($this->environmentConfigValues[$name])) {
            return $defaultValue;
        }

        return $this->environmentConfigValues[$name];
    }

    public function writeEnvironmentConfig($values)
    {
        $config = array_merge($this->readEnvironmentConfig(), $values);
        $content = '<?php' . PHP_EOL . PHP_EOL . 'return ' . var_export($config, true) . ';' . PHP_EOL;

        $this->fileSystem->dumpFile($this->environmentConfigFilePath, $content);

        // Invalidate the cache for the environment file, if opcache is enabled
        if (function_exists('opcache_is_script_cached') && opcache_is_script_cached($this->environmentConfigFilePath)) {
            opcache_invalidate($this->environmentConfigFilePath, true);
        }
    }

    public function readEnvironmentConfig(): array
    {
        if (!file_exists($this->environmentConfigFilePath) || empty(file_get_contents($this->environmentConfigFilePath))) {
            return [];
        }

        return require $this->environmentConfigFilePath;
    }

    public function buildMailerDsn(array $configValues): string
    {
        $dsn = 'sendmail://default';
        if ($configValues['mailer_transport'] === 'smtp') {
            $dsn = 'smtp://';

            if (isset($configValues['mailer_user']) && $configValues['mailer_user'] != '') {
                $dsn .= urlencode($configValues['mailer_user']);

                $mailerPassword = $configValues['mailer_password'] ?? '';
                if (!isset($configValues['mailer_password'])) {
                    $storedConfigValues = $this->readEnvironmentConfig();
                    $mailerPassword = $storedConfigValues['mailer_password'];
                }

                $dsn .= ':' . urlencode($mailerPassword) . '@';
            }

            $dsn .= urlencode($configValues['mailer_host']) . ':' . $configValues['mailer_port'];
        }

        return $dsn;
    }
}