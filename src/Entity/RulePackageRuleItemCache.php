<?php

namespace Mosparo\Entity;

use Mosparo\Repository\RulePackageRuleCacheRepository;
use Doctrine\ORM\Mapping as ORM;
use Mosparo\Rule\PreparedRuleItemTrait;
use Mosparo\Rule\RuleEntityInterface;
use Mosparo\Rule\RuleItemEntityInterface;
use DateTimeInterface;

#[ORM\Table(options: ['engine' => 'InnoDB'])]
#[ORM\Entity(repositoryClass: RulePackageRuleCacheRepository::class)]
#[ORM\Index(name: 'rpric_uuid_idx', fields: ['uuid'])]
#[ORM\Index(name: 'rpric_hashed_idx', fields: ['project', 'type', 'hashedValue'])]
#[ORM\Index(name: 'rpric_rprc_project_idx', fields: ['project', 'rulePackageRuleCache'])]
#[ORM\HasLifecycleCallbacks]
class RulePackageRuleItemCache implements ProjectRelatedEntityInterface, RuleItemEntityInterface
{
    use PreparedRuleItemTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id;

    #[ORM\Column(type: 'guid')]
    private ?string $uuid;

    #[ORM\ManyToOne(targetEntity: RulePackageRuleCache::class, inversedBy: 'items')]
    #[ORM\JoinColumn(nullable: false)]
    private ?RulePackageRuleCache $rulePackageRuleCache = null;

    #[ORM\Column(type: 'string', length: 50)]
    private ?string $type;

    #[ORM\Column(type: 'text')]
    private ?string $value;

    #[ORM\Column(type: 'float', nullable: true)]
    private ?float $spamRatingFactor = null;

    #[ORM\ManyToOne(targetEntity: Project::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Project $project;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?DateTimeInterface $updatedAt = null;

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

    public function getRulePackageRuleCache(): ?RulePackageRuleCache
    {
        return $this->rulePackageRuleCache;
    }

    public function setRulePackageRuleCache(?RulePackageRuleCache $rulePackageRuleCache): self
    {
        $this->rulePackageRuleCache = $rulePackageRuleCache;

        if ($rulePackageRuleCache) {
            $rulePackageRuleCache->addItem($this);
        }

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
        return $this->rulePackageRuleCache;
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

    public function getHash(): string
    {
        return md5($this->uuid . $this->type . $this->value . $this->spamRatingFactor);
    }
}
