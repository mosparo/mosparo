<?php

namespace Mosparo\Doctrine;

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query\Filter\SQLFilter;
use Mosparo\Helper\ActiveProjectHelper;

class ProjectRelatedFilter extends SQLFilter
{
    protected $activeProjectHelper;

    public function setActiveProjectHelper(ActiveProjectHelper $activeProjectHelper)
    {
        $this->activeProjectHelper = $activeProjectHelper;
    }

    public function addFilterConstraint(ClassMetadata $targetEntity, $targetTableAlias)
    {
        // Return if this is not a project aware interface
        if (!$targetEntity->reflClass->implementsInterface('Mosparo\Entity\ProjectRelatedEntityInterface')) {
            return '';
        }

        $activeProject = $this->activeProjectHelper->getActiveProject();
        if ($activeProject === null) {
            return '';
        }

        return sprintf('%s.project_id = ' . $activeProject->getId(), $targetTableAlias);
    }
}