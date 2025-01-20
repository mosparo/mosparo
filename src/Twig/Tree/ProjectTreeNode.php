<?php

namespace Mosparo\Twig\Tree;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Mosparo\Entity\Project;
use Mosparo\Entity\ProjectGroup;

class ProjectTreeNode
{
    protected ?ProjectGroup $projectGroup;

    protected Collection $children;

    protected Collection $projects;

    public function __construct(ProjectGroup $projectGroup = null)
    {
        $this->projectGroup = $projectGroup;
        $this->children = new ArrayCollection();
        $this->projects = new ArrayCollection();
    }

    public function getProjectGroup(): ProjectGroup
    {
        return $this->projectGroup;
    }

    public function addChild(ProjectTreeNode $child): self
    {
        $this->children->add($child);

        return $this;
    }

    public function hasChildren(): bool
    {
        return !$this->children->isEmpty();
    }

    public function getChildren(): Collection
    {
        return $this->children;
    }

    public function findChildForProjectGroup(ProjectGroup $projectGroup): ?self
    {
        if ($this->projectGroup?->getId() === $projectGroup->getId()) {
            return $this;
        }

        if ($this->hasChildren()) {
            foreach ($this->children as $child) {
                $res = $child->findChildForProjectGroup($projectGroup);

                if ($res) {
                    return $res;
                }
            }
        }

        return null;
    }

    public function findNodesForSearchTerm(string $searchTerm): bool
    {
        foreach ($this->children as $child) {
            if (!$child->findNodesForSearchTerm($searchTerm) && !str_contains(mb_strtolower($child->getProjectGroup()->getName()), $searchTerm)) {
                $this->children->removeElement($child);
            }
        }

        foreach ($this->projects as $project) {
            if (!str_contains(mb_strtolower($project->getName()), $searchTerm)) {
                $this->projects->removeElement($project);
            }
        }

        return (!$this->children->isEmpty() || !$this->projects->isEmpty());
    }

    public function sort(): void
    {
        $projectGroupSortCriteria = (new Criteria())->orderBy(['projectGroup.name' => 'ASC']);
        $projectSortCriteria = (new Criteria())->orderBy(['name' => 'ASC']);

        $this->children = $this->children->matching($projectGroupSortCriteria);
        $this->projects = $this->projects->matching($projectSortCriteria);

        foreach ($this->children as $child) {
            $child->sort();
        }
    }

    public function addProject(Project $project): self
    {
        $this->projects->add($project);

        return $this;
    }

    public function hasProjects(): bool
    {
        return !$this->projects->isEmpty();
    }

    public function getProjects(): Collection
    {
        return $this->projects;
    }

    public function __clone()
    {
        $children = new ArrayCollection();
        foreach ($this->children as $child) {
            $children->add(clone $child);
        }
        $this->children = $children;

        $this->projects = clone $this->projects;
    }
}
