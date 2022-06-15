<?php

namespace Mosparo\Entity;

use Mosparo\Repository\RulesetRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=RulesetRepository::class)
 */
class Ruleset implements ProjectRelatedEntityInterface
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private ?int $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private ?string $name;

    /**
     * @ORM\Column(type="text")
     */
    private ?string $url;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private ?float $spamRatingFactor;

    /**
     * @ORM\Column(type="boolean")
     */
    private ?bool $status;

    /**
     * @ORM\OneToOne(targetEntity=RulesetCache::class, mappedBy="ruleset", cascade={"persist", "remove"})
     */
    private ?RulesetCache $rulesetCache;

    /**
     * @ORM\ManyToOne(targetEntity=Project::class)
     * @ORM\JoinColumn(nullable=false)
     */
    private ?Project $project;

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

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function setUrl(string $url): self
    {
        $this->url = $url;

        return $this;
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

    public function getStatus(): ?bool
    {
        return $this->status;
    }

    public function setStatus(bool $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function getRulesetCache(): ?RulesetCache
    {
        return $this->rulesetCache;
    }

    public function setRulesetCache(RulesetCache $rulesetCache): self
    {
        // set the owning side of the relation if necessary
        if ($rulesetCache->getRuleset() !== $this) {
            $rulesetCache->setRuleset($this);
        }

        $this->rulesetCache = $rulesetCache;

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
