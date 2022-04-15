<?php

namespace Mosparo\Doctrine;

use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Events;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Mosparo\Entity\ProjectRelatedEntityInterface;
use Mosparo\Helper\ProjectHelper;

class ProjectRelatedSubscriber implements EventSubscriber
{
    protected $projectHelper;

    public function __construct(ProjectHelper $projectHelper)
    {
        $this->projectHelper = $projectHelper;
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
            $entity->setProject($this->projectHelper->getActiveProject());
        }
    }
}