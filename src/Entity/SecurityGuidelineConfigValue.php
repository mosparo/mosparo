<?php

namespace Mosparo\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=SecurityGuidelineConfigValueRepository::class)
 */
class SecurityGuidelineConfigValue implements ProjectRelatedEntityInterface
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private ?int $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private ?string $name;

    /**
     * @ORM\Column(type="json", nullable=true)
     */
    private $value = null;

    /**
     * @ORM\ManyToOne(targetEntity=SecurityGuideline::class, inversedBy="configValues")
     * @ORM\JoinColumn(nullable=false, onDelete="CASCADE")
     */
    private ?SecurityGuideline $securityGuideline;

    /**
     * @ORM\ManyToOne(targetEntity=Project::class)
     * @ORM\JoinColumn(nullable=false, onDelete="CASCADE")
     */
    private ?Project $project;

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

    public function getValue()
    {
        return $this->value;
    }

    public function setValue($value): self
    {
        $this->value = $value;

        return $this;
    }

    public function getSecurityGuideline(): ?SecurityGuideline
    {
        return $this->securityGuideline;
    }

    public function setSecurityGuideline(?SecurityGuideline $securityGuideline): self
    {
        $this->securityGuideline = $securityGuideline;

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
