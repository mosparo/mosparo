<?php

namespace Mosparo\Entity;

use DateTime;
use DateTimeInterface;
use Mosparo\Repository\SubmitTokenRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SubmitTokenRepository::class)]
class SubmitToken implements ProjectRelatedEntityInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id;

    #[ORM\Column(type: 'hashed')]
    private ?string $ipAddress;

    #[ORM\Column(type: 'text')]
    private ?string $pageTitle;

    #[ORM\Column(type: 'text')]
    private ?string $pageUrl;

    #[ORM\Column(type: 'string', length: 40, nullable: true)]
    private ?string $signature = null;

    #[ORM\Column(type: 'string', length: 64)]
    private ?string $token;

    #[ORM\Column(type: 'datetime')]
    private ?DateTimeInterface $createdAt;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?DateTimeInterface $checkedAt = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?DateTimeInterface $verifiedAt = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?DateTimeInterface $validUntil = null;

    #[ORM\OneToOne(targetEntity: Submission::class)]
    private ?Submission $lastSubmission;

    #[ORM\ManyToOne(targetEntity: Project::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Project $project;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getIpAddress(): ?string
    {
        return $this->ipAddress;
    }

    public function setIpAddress(string $ipAddress): self
    {
        $this->ipAddress = $ipAddress;

        return $this;
    }

    public function getPageTitle(): ?string
    {
        return $this->pageTitle;
    }

    public function setPageTitle(string $pageTitle): self
    {
        $this->pageTitle = $pageTitle;

        return $this;
    }

    public function getPageUrl(): ?string
    {
        return $this->pageUrl;
    }

    public function setPageUrl(string $pageUrl): self
    {
        $this->pageUrl = $pageUrl;

        return $this;
    }

    public function getSignature(): ?string
    {
        return $this->signature;
    }

    public function setSignature(?string $signature): self
    {
        $this->signature = $signature;

        return $this;
    }

    public function getToken(): ?string
    {
        return $this->token;
    }

    public function setToken(string $token): self
    {
        $this->token = $token;

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

    public function getCheckedAt(): ?DateTimeInterface
    {
        return $this->checkedAt;
    }

    public function setCheckedAt(?DateTimeInterface $checkedAt): self
    {
        $this->checkedAt = $checkedAt;

        return $this;
    }

    public function getVerifiedAt(): ?DateTimeInterface
    {
        return $this->verifiedAt;
    }

    public function setVerifiedAt(?DateTimeInterface $verifiedAt): self
    {
        $this->verifiedAt = $verifiedAt;

        return $this;
    }

    public function getValidUntil(): ?DateTimeInterface
    {
        return $this->validUntil;
    }

    public function setValidUntil(DateTimeInterface $validUntil): self
    {
        $this->validUntil = $validUntil;

        return $this;
    }

    public function getLastSubmission(): ?Submission
    {
        return $this->lastSubmission;
    }

    public function setLastSubmission(?Submission $lastSubmission): self
    {
        $this->lastSubmission = $lastSubmission;

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

    public function isValid(): bool
    {
        if ($this->verifiedAt !== null) {
            return false;
        }

        if ($this->validUntil !== null && $this->validUntil < new DateTime()) {
            return false;
        }

        return true;
    }
}
