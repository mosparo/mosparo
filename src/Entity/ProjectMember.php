<?php

namespace Mosparo\Entity;

use Mosparo\Repository\ProjectMemberRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ProjectMemberRepository::class)]
class ProjectMember
{
    const ROLE_READER = 'reader';
    const ROLE_EDITOR = 'editor';
    const ROLE_OWNER = 'owner';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id;

    #[ORM\ManyToOne(targetEntity: Project::class, inversedBy: 'projectMembers')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Project $project;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'projectMemberships')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user;

    #[ORM\Column(type: 'string', length: 30)]
    private ?string $role;

    public function getId(): ?int
    {
        return $this->id;
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

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function getRole(): ?string
    {
        return $this->role;
    }

    public function setRole(string $role): self
    {
        $this->role = $role;

        return $this;
    }
}
