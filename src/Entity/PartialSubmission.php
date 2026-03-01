<?php

namespace Mosparo\Entity;

use DateTimeInterface;
use Mosparo\Repository\PartialSubmissionRepository;
use Doctrine\ORM\Mapping as ORM;
use Mosparo\Trait\SubmissionDataTrait;

#[ORM\Table(options: ['engine' => 'InnoDB'])]
#[ORM\Entity(repositoryClass: PartialSubmissionRepository::class)]
class PartialSubmission implements ProjectRelatedEntityInterface
{
    use SubmissionDataTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id;

    #[ORM\OneToOne(targetEntity: SubmitToken::class, inversedBy: 'partialSubmission')]
    private ?SubmitToken $submitToken;

    #[ORM\Column(type: 'encryptedJson')]
    private array $data = [];

    #[ORM\Column(type: 'json')]
    private array $ignoredFields = [];

    #[ORM\Column(type: 'datetime')]
    private ?DateTimeInterface $updatedAt;

    #[ORM\ManyToOne(targetEntity: Project::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Project $project;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSubmitToken(): ?SubmitToken
    {
        return $this->submitToken;
    }

    public function setSubmitToken(SubmitToken $submitToken): self
    {
        $this->submitToken = $submitToken;

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
