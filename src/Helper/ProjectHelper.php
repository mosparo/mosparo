<?php

namespace Mosparo\Helper;

use Mosparo\Entity\Project;
use Mosparo\Entity\ProjectMember;
use Symfony\Bundle\SecurityBundle\Security;

class ProjectHelper
{
    protected Security $security;

    protected ?Project $activeProject = null;

    public function __construct(Security $security)
    {
        $this->security = $security;
    }

    public function setActiveProject(?Project $activeProject)
    {
        $this->activeProject = $activeProject;
    }

    public function getActiveProject(): ?Project
    {
        return $this->activeProject;
    }

    public function hasActiveProject(): bool
    {
        return ($this->activeProject !== null);
    }

    public function hasRequiredRole($requiredRoles, Project $project = null): bool
    {
        if ($project === null) {
            if ($this->activeProject === null) {
                return false;
            }

            $project = $this->activeProject;
        }

        if ($this->security->isGranted('ROLE_ADMIN')) {
            return true;
        }

        $projectMember = $project->getProjectMember($this->security->getUser());
        if ($projectMember === null) {
            return false;
        }

        if (in_array($projectMember->getRole(), $requiredRoles)) {
            return true;
        }

        return false;
    }

    public function canManage(Project $project = null): bool
    {
        if ($project === null) {
            if ($this->activeProject === null) {
                return false;
            }

            $project = $this->activeProject;
        }

        if ($this->security->isGranted('ROLE_ADMIN')) {
            return true;
        }

        $projectMember = $project->getProjectMember($this->security->getUser());
        if ($projectMember === null) {
            return false;
        }

        if (in_array($projectMember->getRole(), [ProjectMember::ROLE_EDITOR, ProjectMember::ROLE_OWNER])) {
            return true;
        }

        return false;
    }
}