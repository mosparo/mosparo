<?php

namespace Mosparo\Entity;

use Doctrine\ORM\Mapping as ORM;
use Mosparo\Repository\ProjectConfigValueRepository;

#[ORM\Entity(repositoryClass: ProjectConfigValueRepository::class)]
class ProjectConfigValue implements ProjectRelatedEntityInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id;

    #[ORM\Column(type: 'string', length: 255)]
    private ?string $name;

    #[ORM\Column(type: 'object', nullable: true)]
    private $value = null;

    #[ORM\ManyToOne(targetEntity: Project::class, inversedBy: 'configValues')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
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
