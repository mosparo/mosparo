<?php

namespace Mosparo\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Mosparo\Repository\SecurityGuidelineRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Table(options: ['engine' => 'InnoDB'])]
#[ORM\Entity(repositoryClass: SecurityGuidelineRepository::class)]
class SecurityGuideline implements ProjectRelatedEntityInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id;

    #[ORM\Column(type: 'guid')]
    private ?string $uuid;

    #[ORM\Column(type: 'string', length: 255)]
    private ?string $name;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: 'integer')]
    private ?int $priority = 1;

    #[ORM\Column(type: 'json')]
    private array $subnets = [];

    #[ORM\Column(type: 'json')]
    private array $countryCodes = [];

    #[ORM\Column(type: 'json')]
    private array $asNumbers = [];

    #[ORM\Column(type: 'json')]
    private array $formPageUrls = [];

    #[ORM\Column(type: 'json')]
    private array $formActionUrls = [];

    #[ORM\Column(type: 'json')]
    private array $formIds = [];

    #[ORM\OneToMany(targetEntity: SecurityGuidelineConfigValue::class, mappedBy: 'securityGuideline', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $configValues;

    #[ORM\ManyToOne(targetEntity: Project::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Project $project;

    private array $defaultSecurityConfigValues = [
        'overrideSpamDetection' => false,
        'overrideMinimumTime' => false,
        'overrideHoneypotField' => false,
        'overrideDelay' => false,
        'overrideLockout' => false,
        'overrideProofOfWork' => false,
        'overrideEqualSubmissions' => false,
    ];

    public function __construct()
    {
        $this->uuid = uuid_create(UUID_TYPE_RANDOM);
        $this->formOrigins = new ArrayCollection();
        $this->configValues = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUuid(): ?string
    {
        return $this->uuid;
    }

    public function setUuid(string $uuid): self
    {
        $this->uuid = $uuid;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getPriority(): int
    {
        return $this->priority;
    }

    public function setPriority(int $priority): self
    {
        $this->priority = $priority;

        return $this;
    }

    public function getSubnets(): array
    {
        if (empty($this->subnets)) {
            return [];
        }

        return array_values($this->subnets);
    }

    public function setSubnets(array $subnets): self
    {
        $this->subnets = $subnets;

        return $this;
    }

    public function getCountryCodes(): array
    {
        if (empty($this->countryCodes)) {
            return [];
        }

        return array_values($this->countryCodes);
    }

    public function setCountryCodes(array $countryCodes): self
    {
        $this->countryCodes = $countryCodes;

        return $this;
    }

    public function getAsNumbers(): array
    {
        if (empty($this->asNumbers)) {
            return [];
        }

        return array_values($this->asNumbers);
    }

    public function setAsNumbers(array $asNumbers): self
    {
        $this->asNumbers = $asNumbers;

        return $this;
    }

    public function getFormPageUrls(): array
    {
        if (empty($this->formPageUrls)) {
            return [];
        }

        return array_values($this->formPageUrls);
    }

    public function setFormPageUrls(array $formPageUrls): self
    {
        $this->formPageUrls = $formPageUrls;

        return $this;
    }

    public function getFormActionUrls(): array
    {
        if (empty($this->formActionUrls)) {
            return [];
        }

        return array_values($this->formActionUrls);
    }

    public function setFormActionUrls(array $formActionUrls): self
    {
        $this->formActionUrls = $formActionUrls;

        return $this;
    }

    public function getFormIds(): array
    {
        if (empty($this->formIds)) {
            return [];
        }

        return array_values($this->formIds);
    }

    public function setFormIds(array $formIds): self
    {
        $this->formIds = $formIds;

        return $this;
    }


    public function hasCriteria(): bool
    {
        return !(
            empty($this->getSubnets()) && empty($this->getCountryCodes()) && empty($this->getAsNumbers()) &&
            empty($this->getFormPageUrls()) && empty($this->getFormActionUrls()) && empty($this->getFormIds())
        );
    }

    public function getConfigValues(bool $withProjectDefaults = true): ?array
    {
        $configValues = $this->getDefaultConfigValues($withProjectDefaults);
        foreach ($this->configValues as $configValue) {
            $configValues[$configValue->getName()] = $configValue->getValue();
        }

        $configValues['ipAllowList'] = $this->project->getConfigValue('ipAllowList');

        return $configValues;
    }

    public function setConfigValues(array $configValues): self
    {
        foreach ($configValues as $key => $value) {
            $overridden = null;
            if (str_starts_with($key, 'minimumTime')) {
                $overridden = $configValues['overrideMinimumTime'] ?? false;
            } else if (str_starts_with($key, 'honeypotField')) {
                $overridden = $configValues['overrideHoneypotField'] ?? false;
            } else if (str_starts_with($key, 'delay')) {
                $overridden = $configValues['overrideDelay'] ?? false;
            } else if (str_starts_with($key, 'lockout')) {
                $overridden = $configValues['overrideLockout'] ?? false;
            } else if (str_starts_with($key, 'proofOfWork')) {
                $overridden = $configValues['overrideProofOfWork'] ?? false;
            } else if (str_starts_with($key, 'equalSubmissions')) {
                $overridden = $configValues['overrideEqualSubmissions'] ?? false;
            }

            $this->setConfigValue($key, $value, $overridden);
        }

        return $this;
    }

    public function getConfigValue($key)
    {
        $defaultConfigValues = $this->getDefaultConfigValues();
        $configValue = $this->findConfigValue($key);
        if (!$configValue) {
            return $defaultConfigValues[$key] ?? null;
        }

        return $configValue->getValue();
    }

    public function setConfigValue($key, $value, ?bool $overridden = null): self
    {
        $defaultConfigValues = $this->getDefaultConfigValues();
        $configValue = $this->findConfigValue($key);
        if ((isset($defaultConfigValues[$key]) && $value === $defaultConfigValues[$key]) || $value === null || ($overridden !== null && !$overridden)) {
            if ($configValue && $this->configValues->contains($configValue)) {
                $this->configValues->removeElement($configValue);
            }

            return $this;
        }

        if (!$configValue) {
            $configValue = new SecurityGuidelineConfigValue();
            $configValue->setName($key);
            $configValue->setSecurityGuideline($this);
            $this->configValues->add($configValue);
        }

        $configValue->setValue($value);

        return $this;
    }

    protected function findConfigValue($key): ?SecurityGuidelineConfigValue
    {
        $filteredConfigValues = $this->configValues->filter(function ($el) use ($key) {
            if ($el->getName() === $key) {
                return $el;
            }

            return false;
        });

        if ($filteredConfigValues->isEmpty()) {
            return null;
        }

        return $filteredConfigValues->first();
    }

    public function getDefaultConfigValues(bool $withProjectDefaults = true): array
    {
        $defaultValues = $this->defaultSecurityConfigValues + $this->getProject()->getDefaultSecurityConfigValues();
        unset($defaultValues['ipAllowList']);

        if (!$withProjectDefaults) {
            return $defaultValues;
        }

        foreach ($defaultValues as $key => $value) {
            $projectConfigValue = $this->project->getConfigValue($key);

            if ($projectConfigValue !== null) {
                $defaultValues[$key] = $projectConfigValue;
            }
        }

        // Add the spam status and spam score default values from the project
        $defaultValues['spamStatus'] = $this->getProject()->getStatus();
        $defaultValues['spamScore'] = $this->getProject()->getSpamScore();

        return $defaultValues;
    }

    public function getProject(): ?Project
    {
        return $this->project;
    }

    public function setProject(?Project $project): self
    {
        $this->project = $project;

        return $this;
    }

    public function isEqual(array $guideline): bool
    {
        if ($guideline['name'] !== $this->getName()) {
            return false;
        }

        if ($guideline['description'] !== $this->getDescription()) {
            return false;
        }

        if ($guideline['priority'] !== $this->getPriority()) {
            return false;
        }

        if (!$this->areCriteriaEqual($guideline)) {
            return false;
        }

        if (!$this->areSettingsEqual($guideline)) {
            return false;
        }

        return true;
    }

    public function areCriteriaEqual(array $guideline): bool
    {
        if ($guideline['subnets'] !== $this->getSubnets()) {
            return false;
        }

        if ($guideline['countryCodes'] !== $this->getCountryCodes()) {
            return false;
        }

        if ($guideline['asNumbers'] !== $this->getAsNumbers()) {
            return false;
        }

        if ($guideline['formPageUrls'] !== $this->getFormPageUrls()) {
            return false;
        }

        if ($guideline['formActionUrls'] !== $this->getFormActionUrls()) {
            return false;
        }

        if ($guideline['formIds'] !== $this->getFormIds()) {
            return false;
        }

        return true;
    }

    public function areSettingsEqual(array $guideline): bool
    {
        $configValues = $this->getConfigValues(false);
        foreach ($guideline['securitySettings'] as $setting) {
            if (!isset($configValues[$setting['name']]) || $setting['value'] !== $configValues[$setting['name']]) {
                return false;
            }
        }

        return true;
    }

    public function toArray(): array
    {
        $projectDefaultValues = $this->project->getDefaultSecurityConfigValues();
        $projectSecuritySettings = $this->project->getSecurityConfigValues();

        $securitySettings = [];
        foreach ($this->getConfigValues() as $key => $value) {
            // If the default value is the same as the project value and the security guideline value, skip it
            if (
                isset($projectDefaultValues[$key]) && $value === $projectDefaultValues[$key] &&
                isset($projectSecuritySettings[$key]) && $projectDefaultValues[$key] === $projectSecuritySettings[$key]
            ) {
                continue;
            }

            $securitySettings[] = [
                'name' => $key,
                'value' => $value,
            ];
        }

        return [
            'uuid' => $this->getUuid(),
            'name' => $this->getName(),
            'description' => $this->getDescription(),
            'priority' => $this->getPriority(),
            'subnets' => $this->getSubnets(),
            'countryCodes' => $this->getCountryCodes(),
            'asNumbers' => $this->getAsNumbers(),
            'formPageUrls' => $this->getFormPageUrls(),
            'formActionUrls' => $this->getFormActionUrls(),
            'formIds' => $this->getFormIds(),
            'securitySettings' => $securitySettings,
        ];
    }
}
