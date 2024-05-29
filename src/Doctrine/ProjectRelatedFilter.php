<?php

namespace Mosparo\Doctrine;

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query\Filter\SQLFilter;
use Mosparo\Exception;
use Mosparo\Helper\ProjectHelper;

class ProjectRelatedFilter extends SQLFilter
{
    protected ProjectHelper $projectHelper;

    public function setProjectHelper(ProjectHelper $projectHelper)
    {
        $this->projectHelper = $projectHelper;

        if ($this->projectHelper->getActiveProject()) {
            $this->setParameter('projectId', $this->projectHelper->getActiveProject()->getId());
        }
    }

    public function addFilterConstraint(ClassMetadata $targetEntity, $targetTableAlias): string
    {
        // Return if this is not a project aware interface
        if (!$targetEntity->reflClass->implementsInterface('Mosparo\Entity\ProjectRelatedEntityInterface')) {
            return '';
        }

        $activeProject = $this->projectHelper->getActiveProject();
        if ($activeProject === null || !$this->hasParameter('projectId')) {
            dump(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS));
            throw new Exception('Access to a project related entity is not allowed without active project.');
        }

        return sprintf('%s.project_id = ' . $this->getParameter('projectId'), $targetTableAlias);
    }
}