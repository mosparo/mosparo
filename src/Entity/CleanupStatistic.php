<?php

namespace Mosparo\Entity;

use Mosparo\Enum\CleanupExecutor;
use Mosparo\Enum\CleanupStatus;
use Mosparo\Repository\CleanupStatisticRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Table(options: ['engine' => 'InnoDB'])]
#[ORM\Entity(repositoryClass: CleanupStatisticRepository::class)]
class CleanupStatistic
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id;

    #[ORM\Column(type: 'datetime')]
    private \DateTime $dateTime;

    #[ORM\Column(type: 'integer', enumType: CleanupExecutor::class)]
    private CleanupExecutor $cleanupExecutor = CleanupExecutor::UNKNOWN;

    #[ORM\Column(type: 'integer')]
    private int $numberOfStoredSubmitTokens = 0;

    #[ORM\Column(type: 'integer')]
    private int $numberOfDeletedSubmitTokens = 0;

    #[ORM\Column(type: 'integer')]
    private int $numberOfStoredSubmissions = 0;

    #[ORM\Column(type: 'integer')]
    private int $numberOfDeletedSubmissions = 0;

    #[ORM\Column(type: 'float')]
    private float $executionTime = 0.0;

    #[ORM\Column(type: 'integer', enumType: CleanupStatus::class)]
    private CleanupStatus $cleanupStatus = CleanupStatus::UNKNOWN;

    public function __construct()
    {
        $this->dateTime = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDateTime(): ?\DateTime
    {
        return $this->dateTime;
    }

    public function getCleanupExecutor(): ?CleanupExecutor
    {
        return $this->cleanupExecutor;
    }

    public function setCleanupExecutor(CleanupExecutor $cleanupExecutor): self
    {
        $this->cleanupExecutor = $cleanupExecutor;

        return $this;
    }

    public function getNumberOfStoredSubmitTokens(): ?int
    {
        return $this->numberOfStoredSubmitTokens;
    }

    public function setNumberOfStoredSubmitTokens(int $numberOfStoredSubmitTokens): self
    {
        $this->numberOfStoredSubmitTokens = $numberOfStoredSubmitTokens;

        return $this;
    }

    public function increaseNumberOfStoredSubmitTokens(int $add): self
    {
        $this->numberOfStoredSubmitTokens += $add;

        return $this;
    }

    public function getNumberOfDeletedSubmitTokens(): ?int
    {
        return $this->numberOfDeletedSubmitTokens;
    }

    public function setNumberOfDeletedSubmitTokens(int $numberOfDeletedSubmitTokens): self
    {
        $this->numberOfDeletedSubmitTokens = $numberOfDeletedSubmitTokens;

        return $this;
    }

    public function increaseNumberOfDeletedSubmitTokens(int $add): self
    {
        $this->numberOfDeletedSubmitTokens += $add;

        return $this;
    }

    public function getNumberOfStoredSubmissions(): ?int
    {
        return $this->numberOfStoredSubmissions;
    }

    public function setNumberOfStoredSubmissions(int $numberOfStoredSubmissions): self
    {
        $this->numberOfStoredSubmissions = $numberOfStoredSubmissions;

        return $this;
    }

    public function increaseNumberOfStoredSubmissions(int $add): self
    {
        $this->numberOfStoredSubmissions += $add;

        return $this;
    }

    public function getNumberOfDeletedSubmissions(): ?int
    {
        return $this->numberOfDeletedSubmissions;
    }

    public function setNumberOfDeletedSubmissions(int $numberOfDeletedSubmissions): self
    {
        $this->numberOfDeletedSubmissions = $numberOfDeletedSubmissions;

        return $this;
    }

    public function increaseNumberOfDeletedSubmissions(int $add): self
    {
        $this->numberOfDeletedSubmissions += $add;

        return $this;
    }

    public function getExecutionTime(): ?float
    {
        return $this->executionTime;
    }

    public function setExecutionTime(float $executionTime): self
    {
        $this->executionTime = $executionTime;

        return $this;
    }

    public function getCleanupStatus(): ?CleanupStatus
    {
        return $this->cleanupStatus;
    }

    public function setCleanupStatus(CleanupStatus $cleanupStatus): self
    {
        $this->cleanupStatus = $cleanupStatus;

        return $this;
    }
}
