<?php

namespace Mosparo\Entity;

use Mosparo\Repository\ProjectRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=ProjectRepository::class)
 */
class Project
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
    private $name;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $description;

    /**
     * @ORM\Column(type="array", nullable=true)
     */
    private $sites = [];

    /**
     * @ORM\Column(type="string", length=64)
     */
    private $publicKey;

    /**
     * @ORM\Column(type="string", length=64)
     */
    private $privateKey;

    /**
     * @ORM\Column(type="integer", length=15)
     */
    private $status = 1;

    /**
     * @ORM\Column(type="float")
     */
    private $spamScore = 5;

    public function getId(): ?int
    {
        return $this->id;
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

    public function getSites(): ?array
    {
        return $this->sites;
    }

    public function setSites(?array $sites): self
    {
        $this->sites = $sites;

        return $this;
    }

    public function getPublicKey(): ?string
    {
        return $this->publicKey;
    }

    public function setPublicKey(string $publicKey): self
    {
        $this->publicKey = $publicKey;

        return $this;
    }

    public function getPrivateKey(): ?string
    {
        return $this->privateKey;
    }

    public function setPrivateKey(string $privateKey): self
    {
        $this->privateKey = $privateKey;

        return $this;
    }

    public function getStatus(): ?int
    {
        return $this->status;
    }

    public function setStatus(int $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function isActive(): boolean
    {
        return ($this->status);
    }

    public function getSpamScore(): ?float
    {
        return $this->spamScore;
    }

    public function setSpamScore(float $spamScore): self
    {
        $this->spamScore = $spamScore;

        return $this;
    }
}
