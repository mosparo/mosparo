<?php

namespace Mosparo\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Mosparo\Repository\ProjectRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=ProjectRepository::class)
 */
class Project
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $name;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $description;

    /**
     * @ORM\Column(type="array", nullable=true)
     */
    private $hosts = [];

    /**
     * @ORM\Column(type="string", length=64)
     */
    private $publicKey;

    /**
     * @ORM\Column(type="string", length=64)
     */
    private $privateKey;

    /**
     * @ORM\Column(type="integer", length=15)
     */
    private $status = 1;

    /**
     * @ORM\Column(type="float")
     */
    private $spamScore = 5;

    /**
     * @ORM\Column(type="json")
     */
    private $configValues = [];

    /**
     * @var array
     */
    private $defaultConfigValues = [
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

        'ipWhitelist' => '',
    ];

    /**
     * @ORM\OneToMany(targetEntity=ProjectMember::class, mappedBy="project", orphanRemoval=true)
     */
    private $projectMembers;

    public function __construct()
    {
        $this->projectMembers = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getConfigValues(): ?array
    {
        return array_merge($this->defaultConfigValues, $this->configValues);
    }

    public function setConfigValues(array $configValues): self
    {
        $this->configValues = $configValues;

        return $this;
    }

    public function getConfigValue($key)
    {
        if (!isset($this->configValues[$key])) {
            return $this->defaultConfigValues[$key] ?? null;
        }

        return $this->configValues[$key];
    }

    public function setConfigValue($key, $value): self
    {
        if ($value == $this->defaultConfigValues[$key]) {
            if (isset($this->configValues[$key])) {
                unset($this->configValues[$key]);
            }

            return $this;
        }

        $this->configValues[$key] = $value;

        return $this;
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
