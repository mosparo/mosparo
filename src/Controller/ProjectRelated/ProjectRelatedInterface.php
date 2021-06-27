<?php

namespace Mosparo\Controller\ProjectRelated;

use Mosparo\Helper\ProjectHelper;
use Mosparo\Entity\Project;

interface ProjectRelatedInterface
{
    public function setProjectHelper(ProjectHelper $projectHelper);

    public function getActiveProject(): Project;
}