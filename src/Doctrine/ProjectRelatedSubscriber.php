<?php

namespace Mosparo\Doctrine;

use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Events;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Mosparo\Entity\ProjectRelatedEntityInterface;
use Mosparo\Helper\ProjectHelper;

#[AsDoctrineListener(event: Events::prePersist)]
class ProjectRelatedSubscriber
{
    protected ProjectHelper $projectHelper;

    public function __construct(ProjectHelper $projectHelper)
    {
        $this->projectHelper = $projectHelper;
    }

    public function prePersist(LifecycleEventArgs $args): void
    {
        $entity = $args->getObject();

        // Only assign the active project if the entity has no project yet. Otherwise, an explicitly
        // set project (for example, when processing multiple projects in one flush) would be overwritten.
        if ($entity instanceof ProjectRelatedEntityInterface && $entity->getProject() === null && $this->projectHelper->getActiveProject()) {
            $entity->setProject($this->projectHelper->getActiveProject());
        }
    }
}