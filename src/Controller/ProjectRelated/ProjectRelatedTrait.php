<?php

namespace Mosparo\Controller\ProjectRelated;

use Mosparo\Helper\ActiveProjectHelper;
use Mosparo\Entity\Project;

trait ProjectRelatedTrait
{
    /**
     * @var Mosparo\Helper\ActiveProjectHelper
     */
    protected $activeProjectHelper;

    public function setActiveProjectHelper(ActiveProjectHelper $activeProjectHelper)
    {
        $this->activeProjectHelper = $activeProjectHelper;
    }

    public function getActiveProject(): Project
    {
        return $this->activeProjectHelper->getActiveProject();
    }
}