<?php

namespace Mosparo\Entity;

use Mosparo\Repository\DayStatisticRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DayStatisticRepository::class)]
#[ORM\UniqueConstraint(name: 'day_project_idx', columns: ['date', 'project_id'])]
class DayStatistic implements ProjectRelatedEntityInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id;

    #[ORM\Column(type: 'date')]
    private \DateTime $date;

    #[ORM\Column(type: 'integer')]
    private int $numberOfValidSubmissions = 0;

    #[ORM\Column(type: 'integer')]
    private int $numberOfSpamSubmissions = 0;

    #[ORM\ManyToOne(targetEntity: Project::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Project $project;

    public function __construct()
    {
        $this->date = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDate(): ?\DateTime
    {
        return $this->date;
    }

    public function getNumberOfValidSubmissions(): ?int
    {
        return $this->numberOfValidSubmissions;
    }

    public function setNumberOfValidSubmissions(int $numberOfValidSubmissions): self
    {
        $this->numberOfValidSubmissions = $numberOfValidSubmissions;

        return $this;
    }

    public function getNumberOfSpamSubmissions(): ?int
    {
        return $this->numberOfSpamSubmissions;
    }

    public function setNumberOfSpamSubmissions(int $numberOfSpamSubmissions): self
    {
        $this->numberOfSpamSubmissions = $numberOfSpamSubmissions;

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
