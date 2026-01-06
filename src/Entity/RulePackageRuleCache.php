<?php

namespace Mosparo\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Mosparo\Repository\RulePackageRuleCacheRepository;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\Collection;
use Mosparo\Rule\RuleEntityInterface;
use DateTimeInterface;

#[ORM\Table(options: ['engine' => 'InnoDB'])]
#[ORM\Entity(repositoryClass: RulePackageRuleCacheRepository::class)]
#[ORM\Index(name: 'rprc_uuid_idx', fields: ['uuid'])]
class RulePackageRuleCache implements ProjectRelatedEntityInterface, RuleEntityInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id;

    #[ORM\ManyToOne(targetEntity: RulePackageCache::class, inversedBy: 'rules')]
    #[ORM\JoinColumn(nullable: false)]
    private ?RulePackageCache $rulePackageCache;

    #[ORM\Column(type: 'guid')]
    private ?string $uuid;

    #[ORM\Column(type: 'string', length: 255)]
    private ?string $name;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: 'string', length: 30)]
    private ?string $type;

    #[ORM\OneToMany(targetEntity: RulePackageRuleItemCache::class, mappedBy: 'rulePackageRuleCache', orphanRemoval: true)]
    private Collection $items;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $numberOfItems;

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
        $this->items = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getRulePackageCache(): ?RulePackageCache
    {
        return $this->rulePackageCache;
    }

    public function setRulePackageCache(?RulePackageCache $rulePackageCache): self
    {
        $this->rulePackageCache = $rulePackageCache;

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

    public function addItem(RulePackageRuleItemCache $item): self
    {
        if (!$this->items->contains($item)) {
            $this->items[] = $item;
            $item->setRulePackageRuleCache($this);
        }

        return $this;
    }

    public function removeItem(RulePackageRuleItemCache $item): self
    {
        if ($this->items->removeElement($item)) {
            // set the owning side to null (unless already changed)
            if ($item->getRulePackageRuleCache() === $this) {
                $item->setRulePackageRuleCache(null);
            }
        }

        return $this;
    }

    public function findItem($uuid): ?RulePackageRuleItemCache
    {
        foreach ($this->items as $item) {
            if ($item->getUuid() === $uuid) {
                return $item;
            }
        }

        return null;
    }

    public function getNumberOfItems(): ?int
    {
        return $this->numberOfItems;
    }

    public function setNumberOfItems(?int $numberOfItems): self
    {
        $this->numberOfItems = $numberOfItems;

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

    public function getUpdatedAt(): ?DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(DateTimeInterface $updatedAt): self
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function __toString(): string
    {
        return $this->getId() . '-' . spl_object_id($this);
    }

    public function getHash(): string
    {
        return md5($this->uuid . $this->type . $this->name . $this->description . $this->spamRatingFactor);
    }
}
