<?php

namespace Mosparo\Entity;

use DateTime;
use Mosparo\Repository\SubmitTokenRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=SubmitTokenRepository::class)
 */
class SubmitToken implements ProjectRelatedEntityInterface
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $ipAddress;

    /**
     * @ORM\Column(type="string", length=40, nullable=true)
     */
    private $signature;

    /**
     * @ORM\Column(type="string", length=64)
     */
    private $token;

    /**
     * @ORM\Column(type="datetime")
     */
    private $createdAt;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $checkedAt;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $validatedAt;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $validUntil;

    /**
     * @ORM\OneToOne(targetEntity=Submission::class, inversedBy="submitToken", cascade={"persist", "remove"})
     */
    private $submission;

    /**
     * @ORM\ManyToOne(targetEntity=Project::class)
     * @ORM\JoinColumn(nullable=false)
     */
    private $project;

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

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getCheckedAt(): ?\DateTimeInterface
    {
        return $this->checkedAt;
    }

    public function setCheckedAt(?\DateTimeInterface $checkedAt): self
    {
        $this->checkedAt = $checkedAt;

        return $this;
    }

    public function getValidatedAt(): ?\DateTimeInterface
    {
        return $this->validatedAt;
    }

    public function setValidatedAt(?\DateTimeInterface $validatedAt): self
    {
        $this->validatedAt = $validatedAt;

        return $this;
    }

    public function getValidUntil(): ?\DateTimeInterface
    {
        return $this->validUntil;
    }

    public function setValidUntil(\DateTimeInterface $validUntil): self
    {
        $this->validUntil = $validUntil;

        return $this;
    }

    public function getSubmission(): ?Submission
    {
        return $this->submission;
    }

    public function setSubmission(?Submission $submission): self
    {
        $this->submission = $submission;

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
        if ($this->validUntil !== null && $this->validUntil < new DateTime()) {
            return false;
        }

        return true;
    }
}
