<?php

namespace Mosparo\Repository;

use Mosparo\Entity\ProjectGroup;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method ProjectGroup|null find($id, $lockMode = null, $lockVersion = null)
 * @method ProjectGroup|null findOneBy(array $criteria, array $orderBy = null)
 * @method ProjectGroup[]    findAll()
 * @method ProjectGroup[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ProjectGroupRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ProjectGroup::class);
    }
}
