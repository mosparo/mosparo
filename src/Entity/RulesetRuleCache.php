<?php

namespace Mosparo\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Mosparo\Repository\RulesetRuleCacheRepository;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\Collection;
use Mosparo\Rule\RuleEntityInterface;

/**
 * @ORM\Entity(repositoryClass=RulesetRuleCacheRepository::class)
 */
class RulesetRuleCache implements ProjectRelatedEntityInterface, RuleEntityInterface
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity=RulesetCache::class, inversedBy="rules")
     * @ORM\JoinColumn(nullable=false)
     */
    private $rulesetCache;

    /**
     * @ORM\Column(type="guid")
     */
    private $uuid;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $name;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $description;

    /**
     * @ORM\Column(type="string", length=30)
     */
    private $type;

    /**
     * @ORM\OneToMany(targetEntity=RulesetRuleItemCache::class, mappedBy="rulesetRuleCache", orphanRemoval=true)
     */
    private $items;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $spamRatingFactor;

    /**
     * @ORM\ManyToOne(targetEntity=Project::class)
     * @ORM\JoinColumn(nullable=false)
     */
    private $project;

    public function __construct()
    {
        $this->items = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getRulesetCache(): ?RulesetCache
    {
        return $this->rulesetCache;
    }

    public function setRulesetCache(?RulesetCache $rulesetCache): self
    {
        $this->rulesetCache = $rulesetCache;

        return $this;
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

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $this->type = $type;

        return $this;
    }

    /**
     * @return Collection|RuleItem[]
     */
    public function getItems(): Collection
    {
        return $this->items;
    }

    public function addItem(RulesetRuleItemCache $item): self
    {
        if (!$this->items->contains($item)) {
            $this->items[] = $item;
            $item->setRulesetRuleCache($this);
        }

        return $this;
    }

    public function removeItem(RulesetRuleItemCache $item): self
    {
        if ($this->items->removeElement($item)) {
            // set the owning side to null (unless already changed)
            if ($item->getRulesetRuleCache() === $this) {
                $item->setRulesetRuleCache(null);
            }
        }

        return $this;
    }

    public function findItem($uuid): ?RulesetRuleItemCache
    {
        foreach ($this->items as $item) {
            if ($item->getUuid() === $uuid) {
                return $item;
            }
        }

        return null;
    }

    public function getSpamRatingFactor(): ?float
    {
        return $this->spamRatingFactor;
    }

    public function setSpamRatingFactor(?float $spamRatingFactor): self
    {
        $this->spamRatingFactor = $spamRatingFactor;

        return $this;
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
