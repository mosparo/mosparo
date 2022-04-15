<?php

namespace Mosparo\Entity;

use DateTime;
use DateTimeInterface;
use Mosparo\Repository\IpLocalizationRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=IpLocalizationRepository::class)
 */
class IpLocalization
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="hashed")
     */
    private $ipAddress;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $asNumber;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $asOrganization;

    /**
     * @ORM\Column(type="string", length=2, nullable=true)
     */
    private $country;

    /**
     * @ORM\Column(type="datetime")
     */
    private $cachedAt;

    public function __construct()
    {
        $this->cachedAt = new DateTime();
    }

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

    public function getAsNumber(): ?int
    {
        return $this->asNumber;
    }

    public function setAsNumber(?int $asNumber): self
    {
        $this->asNumber = $asNumber;

        return $this;
    }

    public function getAsOrganization(): ?string
    {
        return $this->asOrganization;
    }

    public function setAsOrganization(?string $asOrganization): self
    {
        $this->asOrganization = $asOrganization;

        return $this;
    }

    public function getCountry(): ?string
    {
        return $this->country;
    }

    public function setCountry(?string $country): self
    {
        $this->country = $country;

        return $this;
    }

    public function getCachedAt(): ?DateTimeInterface
    {
        return $this->cachedAt;
    }

    public function setCachedAt(DateTimeInterface $cachedAt): self
    {
        $this->cachedAt = $cachedAt;

        return $this;
    }
}
