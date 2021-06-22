<?php

namespace Mosparo\Doctrine;

use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Events;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Mosparo\Entity\ProjectRelatedEntityInterface;
use Mosparo\Entity\Rule;
use Mosparo\Helper\ActiveProjectHelper;

class ProjectRelatedSubscriber implements EventSubscriber
{
    protected $activeProjectHelper;

    public function __construct(ActiveProjectHelper $activeProjectHelper)
    {
        $this->activeProjectHelper = $activeProjectHelper;
    }

    public function getSubscribedEvents(): array
    {
        return [
            Events::prePersist,
        ];
    }

    public function prePersist(LifecycleEventArgs $args): void
    {
        $entity = $args->getObject();

        if ($entity instanceof ProjectRelatedEntityInterface) {
            $entity->setProject($this->activeProjectHelper->getActiveProject());
        }
    }
}