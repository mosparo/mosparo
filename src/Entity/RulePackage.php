<?php

namespace Mosparo\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Mosparo\Enum\ProcessingJobType;
use Mosparo\Enum\RulePackageType;
use Mosparo\Repository\RulePackageRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Table(options: ['engine' => 'InnoDB'])]
#[ORM\Entity(repositoryClass: RulePackageRepository::class)]
class RulePackage implements ProjectRelatedEntityInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id;

    #[ORM\Column(type: 'guid')]
    private ?string $uuid;

    #[ORM\Column(type: 'integer', enumType: RulePackageType::class, options: ['default' => 1])]
    private RulePackageType $type = RulePackageType::AUTOMATICALLY_FROM_URL;

    #[ORM\Column(type: 'string', length: 255)]
    private ?string $name;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $source;

    #[ORM\Column(type: 'float', nullable: true)]
    private ?float $spamRatingFactor = null;

    #[ORM\Column(type: 'boolean')]
    private ?bool $status;

    #[ORM\OneToOne(targetEntity: RulePackageCache::class, mappedBy: 'rulePackage', cascade: ['persist', 'remove'])]
    private ?RulePackageCache $rulePackageCache = null;

    #[ORM\OneToMany(targetEntity: RulePackageProcessingJob::class, mappedBy: 'rulePackage', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $processingJobs;

    #[ORM\ManyToOne(targetEntity: Project::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Project $project;

    public function __construct()
    {
        $this->uuid = uuid_create(UUID_TYPE_RANDOM);
        $this->processingJobs = new ArrayCollection();
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

    public function getType(): ?RulePackageType
    {
        return $this->type;
    }

    public function setType(RulePackageType $type): self
    {
        $this->type = $type;

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

    public function getSource(): ?string
    {
        return $this->source;
    }

    public function setSource(?string $source): self
    {
        $this->source = $source;

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

    public function isActive(): bool
    {
        return ($this->status);
    }

    public function getRulePackageCache(): ?RulePackageCache
    {
        return $this->rulePackageCache;
    }

    public function setRulePackageCache(RulePackageCache $rulePackageCache): self
    {
        // set the owning side of the relation if necessary
        if ($rulePackageCache->getRulePackage() !== $this) {
            $rulePackageCache->setRulePackage($this);
        }

        $this->rulePackageCache = $rulePackageCache;

        return $this;
    }

    /**
     * @return Collection|RulePackageRuleCache[]
     */
    public function getProcessingJobs(): Collection
    {
        return $this->processingJobs;
    }

    public function getFirstProcessingJob(ProcessingJobType $type): ?RulePackageProcessingJob
    {
        return $this->processingJobs->findFirst(function (int $key, RulePackageProcessingJob $item) use ($type) {
            if ($item->getType() === $type) {
                return $item;
            }

            return null;
        });
    }

    public function addProcessingJob(RulePackageProcessingJob $processingJob): self
    {
        $this->processingJobs->add($processingJob);

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
