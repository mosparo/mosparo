<?php

namespace Mosparo\Entity;

use DateTimeInterface;
use Mosparo\Enum\ProcessingJobType;
use Mosparo\Repository\RulePackageProcessingJobRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Table(options: ['engine' => 'InnoDB'])]
#[ORM\Entity(repositoryClass: RulePackageProcessingJobRepository::class)]
class RulePackageProcessingJob implements ProjectRelatedEntityInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id;

    #[ORM\ManyToOne(targetEntity: RulePackage::class, inversedBy: 'processingJobs')]
    private ?RulePackage $rulePackage = null;

    #[ORM\Column(type: 'datetime')]
    private DateTimeInterface $createdAt;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?DateTimeInterface $sourceUpdatedAt;

    #[ORM\Column(type: 'integer', enumType: ProcessingJobType::class)]
    private ProcessingJobType $type = ProcessingJobType::UNKNOWN;

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $mimetype = null;

    #[ORM\Column(type: 'array')]
    private array $processedImportData = [];

    #[ORM\Column(type: 'integer')]
    private int $importTasks = 0;

    #[ORM\Column(type: 'integer')]
    private int $processedImportTasks = 0;

    #[ORM\Column(type: 'array')]
    private array $processedCleanupData = [];

    #[ORM\Column(type: 'integer')]
    private int $cleanupTasks = 0;

    #[ORM\Column(type: 'integer')]
    private int $processedCleanupTasks = 0;

    #[ORM\ManyToOne(targetEntity: Project::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Project $project;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
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
        $rulePackage->addProcessingJob($this);

        return $this;
    }

    public function getCreatedAt(): ?DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(DateTimeInterface $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getSourceUpdatedAt(): ?DateTimeInterface
    {
        return $this->sourceUpdatedAt;
    }

    public function setSourceUpdatedAt(DateTimeInterface $sourceUpdatedAt): self
    {
        $this->sourceUpdatedAt = $sourceUpdatedAt;

        return $this;
    }

    public function getType(): ?ProcessingJobType
    {
        return $this->type;
    }

    public function setType(ProcessingJobType $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getMimetype(): string
    {
        return $this->mimetype;
    }

    public function setMimetype(string $mimetype): self
    {
        $this->mimetype = $mimetype;

        return $this;
    }

    public function getProcessedImportData(): array
    {
        return $this->processedImportData;
    }

    public function getProcessedImportDataWithKey(string $key): mixed
    {
        return $this->processedImportData[$key] ?? null;
    }

    public function setProcessedImportData(array $processedImportData): self
    {
        $this->processedImportData = $processedImportData;

        return $this;
    }

    public function setProcessedImportDataWithKey(string $key, mixed $value): self
    {
        $this->processedImportData[$key] = $value;

        return $this;
    }

    public function addProcessedImportDataWithKey(string $key, mixed $value): self
    {
        $this->processedImportData[$key][] = $value;

        return $this;
    }

    public function getImportTasks(): int
    {
        return $this->importTasks;
    }

    public function setImportTasks(int $importTasks): self
    {
        $this->importTasks = $importTasks;

        return $this;
    }
    
    public function getProcessedImportTasks(): int
    {
        return $this->processedImportTasks;
    }

    public function setProcessedImportTasks(int $processedImportTasks): self
    {
        $this->processedImportTasks = $processedImportTasks;

        return $this;
    }

    public function increaseProcessedImportTasks(int $amount = 1): self
    {
        $this->processedImportTasks += $amount;

        return $this;
    }

    public function getProcessedCleanupData(): array
    {
        return $this->processedCleanupData;
    }

    public function getProcessedCleanupDataWithKey(string $key): mixed
    {
        return $this->processedCleanupData[$key] ?? null;
    }

    public function setProcessedCleanupData(array $processedCleanupData): self
    {
        $this->processedCleanupData = $processedCleanupData;

        return $this;
    }

    public function setProcessedCleanupDataWithKey(string $key, mixed $value): self
    {
        $this->processedCleanupData[$key] = $value;

        return $this;
    }

    public function addProcessedCleanupDataWithKey(string $key, mixed $value): self
    {
        $this->processedCleanupData[$key][] = $value;

        return $this;
    }
    
    public function getCleanupTasks(): int
    {
        return $this->cleanupTasks;
    }

    public function setCleanupTasks(int $cleanupTasks): self
    {
        $this->cleanupTasks = $cleanupTasks;

        return $this;
    }

    public function getProcessedCleanupTasks(): int
    {
        return $this->processedCleanupTasks;
    }

    public function setProcessedCleanupTasks(int $processedCleanupTasks): self
    {
        $this->processedCleanupTasks = $processedCleanupTasks;

        return $this;
    }

    public function increaseProcessedCleanupTasks(int $amount = 1): self
    {
        $this->processedCleanupTasks += $amount;

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
