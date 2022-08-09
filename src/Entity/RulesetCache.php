<?php

namespace Mosparo\Entity;

use DateTimeInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Mosparo\Repository\RulesetCacheRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=RulesetCacheRepository::class)
 */
class RulesetCache implements ProjectRelatedEntityInterface
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private ?int $id;

    /**
     * @ORM\OneToOne(targetEntity=Ruleset::class, inversedBy="rulesetCache", cascade={"persist", "remove"})
     * @ORM\JoinColumn(nullable=false)
     */
    private ?Ruleset $ruleset = null;

    /**
     * @ORM\Column(type="datetime")
     */
    private ?DateTimeInterface $refreshedAt = null;

    /**
     * @ORM\Column(type="datetime")
     */
    private ?DateTimeInterface $updatedAt = null;

    /**
     * @ORM\Column(type="integer")
     */
    private ?int $refreshInterval = 86400;

    /**
     * @ORM\OneToMany(targetEntity=RulesetRuleCache::class, mappedBy="rulesetCache", orphanRemoval=true)
     */
    private Collection $rules;

    /**
     * @ORM\ManyToOne(targetEntity=Project::class)
     * @ORM\JoinColumn(nullable=false)
     */
    private ?Project $project;

    public function __construct()
    {
        $this->rules = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getRuleset(): ?Ruleset
    {
        return $this->ruleset;
    }

    public function setRuleset(Ruleset $ruleset): self
    {
        $this->ruleset = $ruleset;

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
     * @return Collection|RulesetRuleCache[]
     */
    public function getRules(): Collection
    {
        return $this->rules;
    }

    public function addRule(RulesetRuleCache $rule): self
    {
        if (!$this->rules->contains($rule)) {
            $this->rules[] = $rule;
            $rule->setRulesetCache($this);
        }

        return $this;
    }

    public function removeRule(RulesetRuleCache $rule): self
    {
        if ($this->rules->removeElement($rule)) {
            // set the owning side to null (unless already changed)
            if ($rule->getRulesetCache() === $this) {
                $rule->setRulesetCache(null);
            }
        }

        return $this;
    }

    public function findRule($uuid): ?RulesetRuleCache
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
