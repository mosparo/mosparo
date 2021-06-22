<?php

namespace Mosparo\Helper;

use Mosparo\Entity\Project;

class ActiveProjectHelper
{
    protected $activeProject;

    public function setActiveProject(Project $activeProject)
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
}