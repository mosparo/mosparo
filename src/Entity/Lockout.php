<?php

namespace Mosparo\Entity;

use Mosparo\Repository\LockoutRepository;
use Doctrine\ORM\Mapping as ORM;
use DateTimeInterface;

#[ORM\Entity(repositoryClass: LockoutRepository::class)]
class Lockout
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id;

    #[ORM\Column(type: 'hashed')]
    private ?string $ipAddress;

    #[ORM\Column(type: 'datetime')]
    private ?DateTimeInterface $startedAt;

    #[ORM\Column(type: 'integer')]
    private ?int $duration;

    #[ORM\Column(type: 'datetime')]
    private ?DateTimeInterface $validUntil;

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

    public function getStartedAt(): ?DateTimeInterface
    {
        return $this->startedAt;
    }

    public function setStartedAt(DateTimeInterface $startedAt): self
    {
        $this->startedAt = $startedAt;

        return $this;
    }

    public function getDuration(): ?int
    {
        return $this->duration;
    }

    public function setDuration(int $duration): self
    {
        // Limit the duration to one year to prevent an out-of-range error.
        if ($duration > (365 * 86400)) {
            $duration = (365 * 86400);
        }

        $this->duration = $duration;

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
}
