<?php

namespace Mosparo\Entity;

use Mosparo\Repository\RulesetRuleCacheRepository;
use Doctrine\ORM\Mapping as ORM;
use Mosparo\Rule\RuleItemEntityInterface;

#[ORM\Entity(repositoryClass: RulesetRuleCacheRepository::class)]
class RulesetRuleItemCache implements ProjectRelatedEntityInterface, RuleItemEntityInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id;

    #[ORM\Column(type: 'guid')]
    private ?string $uuid;

    #[ORM\ManyToOne(targetEntity: RulesetRuleCache::class, inversedBy: 'items')]
    #[ORM\JoinColumn(nullable: false)]
    private ?RulesetRuleCache $rulesetRuleCache = null;

    #[ORM\Column(type: 'string', length: 255)]
    private ?string $type;

    #[ORM\Column(type: 'text')]
    private ?string $value;

    #[ORM\Column(type: 'float', nullable: true)]
    private ?float $spamRatingFactor = null;

    #[ORM\ManyToOne(targetEntity: Project::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Project $project;

    public function __construct()
    {
        $this->uuid = uuid_create(UUID_TYPE_RANDOM);
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

    public function getRulesetRuleCache(): ?RulesetRuleCache
    {
        return $this->rulesetRuleCache;
    }

    public function setRulesetRuleCache(?RulesetRuleCache $rulesetRuleCache): self
    {
        $this->rulesetRuleCache = $rulesetRuleCache;

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

    public function getValue(): ?string
    {
        return $this->value;
    }

    public function setValue(string $value): self
    {
        $this->value = $value;

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
