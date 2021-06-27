<?php

namespace Mosparo\Helper;

use Doctrine\ORM\EntityManagerInterface;
use Mosparo\Entity\ConfigValue;

class ConfigHelper
{
    protected $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
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
}