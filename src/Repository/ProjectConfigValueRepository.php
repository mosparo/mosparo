<?php

namespace Mosparo\Repository;

use Mosparo\Entity\ProjectConfigValue;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method ProjectConfigValue|null find($id, $lockMode = null, $lockVersion = null)
 * @method ProjectConfigValue|null findOneBy(array $criteria, array $orderBy = null)
 * @method ProjectConfigValue[]    findAll()
 * @method ProjectConfigValue[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ProjectConfigValueRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ProjectConfigValue::class);
    }

    // /**
    //  * @return ProjectConfigValue[] Returns an array of ProjectConfigValue objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('p.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?ProjectConfigValue
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
