<?php

namespace Mosparo\Doctrine;

use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\Common\EventSubscriber;
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

        if ($entity instanceof ProjectRelatedEntityInterface && $this->projectHelper->getActiveProject()) {
            $entity->setProject($this->projectHelper->getActiveProject());
        }
    }
}