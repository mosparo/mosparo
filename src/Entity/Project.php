<?php

namespace Mosparo\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Mosparo\Enum\LanguageSource;
use Mosparo\Repository\ProjectRepository;
use Doctrine\ORM\Mapping as ORM;
use Mosparo\Util\DateRangeUtil;

#[ORM\Table(options: ['engine' => 'InnoDB'])]
#[ORM\Entity(repositoryClass: ProjectRepository::class)]
class Project
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id;

    #[ORM\Column(type: 'guid')]
    private ?string $uuid;

    #[ORM\ManyToOne(targetEntity: ProjectGroup::class, inversedBy: 'projects')]
    #[ORM\JoinColumn(nullable: true)]
    private ?ProjectGroup $projectGroup = null;

    #[ORM\Column(type: 'string', length: 255)]
    private ?string $name;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: 'array', nullable: true)]
    private array $hosts = [];

    #[ORM\Column(type: 'string', length: 64)]
    private ?string $publicKey;

    #[ORM\Column(type: 'encrypted')]
    private ?string $privateKey;

    #[ORM\Column(type: 'smallint')]
    private int $status = 1;

    #[ORM\Column(type: 'float')]
    private float $spamScore = 5;

    #[ORM\Column(type: 'string', length: 7)]
    private ?string $statisticStorageLimit;

    #[ORM\Column(type: 'boolean')]
    private bool $apiDebugMode = false;

    #[ORM\Column(type: 'boolean')]
    private bool $verificationSimulationMode = false;

    #[ORM\Column(type: 'smallint', enumType: LanguageSource::class)]
    private LanguageSource $languageSource = LanguageSource::BROWSER_FALLBACK;

    #[ORM\OneToMany(targetEntity: ProjectConfigValue::class, mappedBy: 'project', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $configValues;

    #[ORM\OneToMany(targetEntity: ProjectMember::class, mappedBy: 'project', orphanRemoval: true)]
    private Collection $projectMembers;

    /**
     * @var array
     */
    private array $defaultGeneralConfigValues = [
        'designMode' => '',
        'boxSize' => 'medium',
        'positionContainer' => 'relative',
        'displayContent' => 'block',

        // Visible: Simple
        'colorWebsiteBackground' => 'rgb(255, 255, 255)',
        'colorWebsiteForeground' => 'rgb(0, 0, 0)',
        'colorWebsiteAccent' => 'rgb(32, 107, 196)',
        'colorHover' => 'rgb(32, 107, 196, 0.5)',
        'colorSuccess' => 'rgb(0, 255, 0)',
        'colorFailure' => 'rgb(255, 0, 0)',

        // Visible: Advanced
        'boxRadius' => 11,
        'boxBorderWidth' => 3,
        'checkboxRadius' => 20,
        'checkboxBorderWidth' => 3,
        'colorBackground' => 'rgb(255, 255, 255)',
        'colorBorder' => 'rgb(0, 0, 0)',
        'colorCheckbox' => 'rgb(0, 0, 0)',
        'colorText' => 'rgb(0, 0, 0)',
        'colorShadow' => 'rgba(170, 170, 170, 0.3)',
        'colorShadowInset' => 'transparent',
        'colorFocusCheckbox' => 'rgb(170, 170, 170)',
        'colorFocusCheckboxShadow' => 'rgba(170, 170, 170, 0.3)',
        'colorLoadingCheckbox' => 'transparent',
        'colorLoadingCheckboxAnimatedCircle' => 'rgb(0, 0, 255)',
        'colorSuccessBackground' => 'rgb(255, 255, 255)',
        'colorSuccessBorder' => 'rgb(0, 0, 0)',
        'colorSuccessCheckbox' => 'rgb(0, 170, 0)',
        'colorSuccessText' => 'rgb(0, 0, 0)',
        'colorSuccessShadow' => 'rgba(170, 170, 170, 0.3)',
        'colorSuccessShadowInset' => 'transparent',
        'colorFailureBackground' => 'rgb(255, 255, 255)',
        'colorFailureBorder' => 'rgb(0, 0, 0)',
        'colorFailureCheckbox' => 'rgb(255, 0, 0)',
        'colorFailureText' => 'rgb(0, 0, 0)',
        'colorFailureTextError' => 'rgb(255, 0, 0)',
        'colorFailureShadow' => 'rgba(170, 170, 170, 0.3)',
        'colorFailureShadowInset' => 'transparent',
        'showPingAnimation' => true,
        'showMosparoLogo' => true,

        // Invisible: Simple
        'fullPageOverlay' => true,
        'colorLoaderBackground' => 'rgba(255, 255, 255, 0.8)',
        'colorLoaderText' => 'rgb(0, 0, 0)',
        'colorLoaderCircle' => 'rgb(0, 0, 255)',
    ];

    /**
     * @var array
     */
    private array $defaultSecurityConfigValues = [
        'minimumTimeActive' => false,
        'minimumTimeSeconds' => 10,

        'honeypotFieldActive' => false,
        'honeypotFieldName' => '',

        'delayActive' => false,
        'delayNumberOfRequests' => 30,
        'delayDetectionTimeFrame' => 30,
        'delayTime' => 60,
        'delayMultiplicator' => 1.5,

        'lockoutActive' => false,
        'lockoutNumberOfRequests' => 60,
        'lockoutDetectionTimeFrame' => 60,
        'lockoutTime' => 300,
        'lockoutMultiplicator' => 1.2,

        'proofOfWorkActive' => false,
        'proofOfWorkComplexity' => 5,
        'proofOfWorkDynamicComplexityActive' => true,
        'proofOfWorkDynamicComplexityMaxComplexity' => 7,
        'proofOfWorkDynamicComplexityNumberOfSubmissions' => 30,
        'proofOfWorkDynamicComplexityTimeFrame' => 300,
        'proofOfWorkDynamicComplexityBasedOnIpAddress' => true,

        'equalSubmissionsActive' => false,
        'equalSubmissionsNumberOfEqualSubmissions' => 3,
        'equalSubmissionsTimeFrame' => 300,
        'equalSubmissionsBasedOnIpAddress' => true,

        'ipAllowList' => '',
    ];

    public function __construct()
    {
        $this->uuid = uuid_create(UUID_TYPE_RANDOM);
        $this->statisticStorageLimit = DateRangeUtil::DATE_RANGE_FOREVER;
        $this->configValues = new ArrayCollection();
        $this->projectMembers = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUuid(): ?string
    {
        return $this->uuid;
    }

    public function getProjectGroup(): ?ProjectGroup
    {
        return $this->projectGroup;
    }

    public function setProjectGroup(?ProjectGroup $projectGroup): self
    {
        $this->projectGroup = $projectGroup;

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

    public function getHosts(): ?array
    {
        return $this->hosts;
    }

    public function setHosts(?array $hosts): self
    {
        $this->hosts = $hosts;

        return $this;
    }

    public function getPublicKey(): ?string
    {
        return $this->publicKey;
    }

    public function setPublicKey(string $publicKey): self
    {
        $this->publicKey = $publicKey;

        return $this;
    }

    public function getPrivateKey(): ?string
    {
        return $this->privateKey;
    }

    public function setPrivateKey(string $privateKey): self
    {
        $this->privateKey = $privateKey;

        return $this;
    }

    public function getStatus(): ?int
    {
        return $this->status;
    }

    public function setStatus(int $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function isActive(): bool
    {
        return ($this->status);
    }

    public function getSpamScore(): ?float
    {
        return $this->spamScore;
    }

    public function setSpamScore(float $spamScore): self
    {
        $this->spamScore = $spamScore;

        return $this;
    }

    public function getStatisticStorageLimit(): ?string
    {
        return $this->statisticStorageLimit;
    }

    public function setStatisticStorageLimit(string $statisticStorageLimit): self
    {
        $this->statisticStorageLimit = $statisticStorageLimit;

        return $this;
    }

    public function isApiDebugMode(): ?bool
    {
        return $this->apiDebugMode;
    }

    public function setApiDebugMode(bool $apiDebugMode): self
    {
        $this->apiDebugMode = $apiDebugMode;

        return $this;
    }

    public function isVerificationSimulationMode(): ?bool
    {
        return $this->verificationSimulationMode;
    }

    public function setVerificationSimulationMode(bool $verificationSimulationMode): self
    {
        $this->verificationSimulationMode = $verificationSimulationMode;

        return $this;
    }

    public function getLanguageSource(): LanguageSource
    {
        return $this->languageSource;
    }

    public function setLanguageSource(LanguageSource $languageSource): self
    {
        $this->languageSource = $languageSource;

        return $this;
    }

    public function getConfigValues(): ?array
    {
        $configValues = $this->getDefaultConfigValues();
        foreach ($this->configValues as $configValue) {
            $configValues[$configValue->getName()] = $configValue->getValue();
        }

        return $configValues;
    }

    public function getSecurityConfigValues(): array
    {
        $defaultValues = $this->defaultSecurityConfigValues;

        foreach ($defaultValues as $key => $value) {
            $projectConfigValue = $this->getConfigValue($key);

            if ($projectConfigValue !== null) {
                $defaultValues[$key] = $projectConfigValue;
            }
        }

        return $defaultValues;
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

    public function setConfigValue($key, $value): self
    {
        $defaultConfigValues = $this->getDefaultConfigValues();

        $configValue = $this->findConfigValue($key);
        if ((isset($defaultConfigValues[$key]) && $value === $defaultConfigValues[$key]) || $value === null) {
            if ($configValue && $this->configValues->contains($configValue)) {
                $this->configValues->removeElement($configValue);
            }

            return $this;
        }

        if (!$configValue) {
            $configValue = new ProjectConfigValue();
            $configValue->setName($key);
            $this->configValues->add($configValue);
        }

        $configValue->setValue($value);

        return $this;
    }

    protected function findConfigValue($key): ?ProjectConfigValue
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

        $firstConfigValue = $filteredConfigValues->first();

        // If there is more than one value, remove all other than the first one.
        if ($filteredConfigValues->count() > 1) {
            foreach ($filteredConfigValues as $configValue) {
                if ($configValue !== $firstConfigValue) {
                    $this->configValues->removeElement($configValue);
                }
            }
        }

        return $firstConfigValue;
    }

    public function getDefaultSecurityConfigValues(): ?array
    {
        return $this->defaultSecurityConfigValues;
    }

    public function getDefaultConfigValues(): ?array
    {
        return $this->defaultGeneralConfigValues + $this->defaultSecurityConfigValues;
    }

    public function getDesignMode(): string
    {
        $designMode = $this->findConfigValue('designMode');
        if ($designMode) {
            return $designMode->getValue();
        }

        return 'simple';
    }

    /**
     * @return Collection|ProjectMember[]
     */
    public function getProjectMembers(): Collection
    {
        return $this->projectMembers;
    }

    public function addProjectMember(ProjectMember $projectMember): self
    {
        if (!$this->projectMembers->contains($projectMember)) {
            $this->projectMembers[] = $projectMember;
            $projectMember->setProject($this);
        }

        return $this;
    }

    public function removeProjectMember(ProjectMember $projectMember): self
    {
        if ($this->projectMembers->removeElement($projectMember)) {
            // set the owning side to null (unless already changed)
            if ($projectMember->getProject() === $this) {
                $projectMember->setProject(null);
            }
        }

        return $this;
    }

    public function getProjectMember(User $user): ?ProjectMember
    {
        foreach ($this->projectMembers as $projectMember) {
            if ($projectMember->getUser() == $user) {
                return $projectMember;
            }
        }

        return null;
    }

    public function isProjectMember(User $user): bool
    {
        foreach ($this->projectMembers as $projectMember) {
            if ($projectMember->getUser() == $user) {
                return true;
            }
        }

        return false;
    }

    public function isProjectOwner(User $user): bool
    {
        foreach ($this->projectMembers as $projectMember) {
            if ($projectMember->getUser() == $user && $projectMember->getRole() === ProjectMember::ROLE_OWNER) {
                return true;
            }
        }

        return false;
    }
}
