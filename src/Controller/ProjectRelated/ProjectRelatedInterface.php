<?php

namespace Mosparo\Controller\ProjectRelated;

use Mosparo\Helper\ActiveProjectHelper;
use Mosparo\Entity\Project;

interface ProjectRelatedInterface
{
    public function setActiveProjectHelper(ActiveProjectHelper $activeProjectHelper);

    public function getActiveProject(): Project;
}