<?php

namespace Mosparo\Entity;

use DateTimeInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Mosparo\Repository\RulePackageCacheRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: RulePackageCacheRepository::class)]
class RulePackageCache implements ProjectRelatedEntityInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id;

    #[ORM\OneToOne(targetEntity: RulePackage::class, inversedBy: 'rulePackageCache', cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(nullable: false)]
    private ?RulePackage $rulePackage = null;

    #[ORM\Column(type: 'datetime')]
    private ?DateTimeInterface $refreshedAt = null;

    #[ORM\Column(type: 'datetime')]
    private ?DateTimeInterface $updatedAt = null;

    #[ORM\Column(type: 'integer')]
    private ?int $refreshInterval = 86400;

    #[ORM\OneToMany(targetEntity: RulePackageRuleCache::class, mappedBy: 'rulePackageCache', orphanRemoval :true)]
    private Collection $rules;

    #[ORM\ManyToOne(targetEntity: Project::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Project $project;

    public function __construct()
    {
        $this->rules = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getRulePackage(): ?RulePackage
    {
        return $this->rulePackage;
    }

    public function setRulePackage(RulePackage $rulePackage): self
    {
        $this->rulePackage = $rulePackage;

        return $this;
    }

    public function getRefreshedAt(): ?DateTimeInterface
    {
        return $this->refreshedAt;
    }

    public function setRefreshedAt(DateTimeInterface $refreshedAt): self
    {
        $this->refreshedAt = $refreshedAt;

        return $this;
    }

    public function getUpdatedAt(): ?DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(DateTimeInterface $updatedAt): self
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function getRefreshInterval(): ?int
    {
        return $this->refreshInterval;
    }

    public function setRefreshInterval(int $refreshInterval): self
    {
        $this->refreshInterval = $refreshInterval;

        return $this;
    }

    /**
     * @return Collection|RulePackageRuleCache[]
     */
    public function getRules(): Collection
    {
        return $this->rules;
    }

    public function addRule(RulePackageRuleCache $rule): self
    {
        if (!$this->rules->contains($rule)) {
            $this->rules[] = $rule;
            $rule->setRulePackageCache($this);
        }

        return $this;
    }

    public function removeRule(RulePackageRuleCache $rule): self
    {
        if ($this->rules->removeElement($rule)) {
            // set the owning side to null (unless already changed)
            if ($rule->getRulePackageCache() === $this) {
                $rule->setRulePackageCache(null);
            }
        }

        return $this;
    }

    public function findRule($uuid): ?RulePackageRuleCache
    {
        foreach ($this->rules as $rule) {
            if ($rule->getUuid() === $uuid) {
                return $rule;
            }
        }

        return null;
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
}
