<?php

namespace Mosparo\Entity;

use Mosparo\Repository\RuleItemRepository;
use Doctrine\ORM\Mapping as ORM;
use Mosparo\Rule\PreparedRuleItemTrait;
use Mosparo\Rule\RuleEntityInterface;
use Mosparo\Rule\RuleItemEntityInterface;

#[ORM\Entity(repositoryClass: RuleItemRepository::class)]
#[ORM\Index(name: 'ri_uuid_idx', fields: ['uuid'])]
#[ORM\Index(name: 'ri_hashed_idx', fields: ['project', 'type', 'hashedValue'])]
#[ORM\HasLifecycleCallbacks]
class RuleItem implements ProjectRelatedEntityInterface, RuleItemEntityInterface
{
    use PreparedRuleItemTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id;

    #[ORM\Column(type: 'guid')]
    private ?string $uuid;

    #[ORM\ManyToOne(targetEntity: Rule::class, inversedBy: 'items')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Rule $rule;

    #[ORM\Column(type: 'string', length: 50)]
    private ?string $type;

    #[ORM\Column(type: 'text')]
    private ?string $value;

    #[ORM\Column(type: 'float', nullable: true)]
    private ?float $spamRatingFactor = 1.0;

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

    public function getRule(): ?Rule
    {
        return $this->rule;
    }

    public function setRule(?Rule $rule): self
    {
        $this->rule = $rule;

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

    public function getParent(): RuleEntityInterface
    {
        return $this->rule;
    }
}
