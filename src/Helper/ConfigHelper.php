<?php

namespace Mosparo\Helper;

use Doctrine\ORM\EntityManagerInterface;
use Mosparo\Entity\ConfigValue;
use Symfony\Component\Filesystem\Filesystem;

class ConfigHelper
{
    protected $entityManager;

    protected $fileSystem;

    protected $environmentConfigFilePath;

    public function __construct(EntityManagerInterface $entityManager, Filesystem $fileSystem, $projectDirectory)
    {
        $this->entityManager = $entityManager;
        $this->fileSystem = $fileSystem;
        $this->environmentConfigFilePath = $projectDirectory . '/config/env.mosparo.php';
    }

    public function getConfigValue($name, $defaultValue = false)
    {
        $configValueRepository = $this->entityManager->getRepository(ConfigValue::class);

        $configValue = $configValueRepository->findOneBy(['name' => $name]);
        if ($configValue === null) {
            return $defaultValue;
        }

        return $configValue->getValue();
    }

    public function setConfigValue($name, $value): self
    {
        $configValueRepository = $this->entityManager->getRepository(ConfigValue::class);

        $configValue = $configValueRepository->findOneBy(['name' => $name]);
        if ($configValue === null) {
            $configValue = new ConfigValue();
            $configValue->setName($name);

            $this->entityManager->persist($configValue);
        }

        $configValue->setValue($value);
        $this->entityManager->flush();

        return $this;
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
        if (!file_exists($this->environmentConfigFilePath)) {
            return [];
        }

        return require $this->environmentConfigFilePath;
    }
}