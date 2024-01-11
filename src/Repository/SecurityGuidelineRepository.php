<?php

namespace Mosparo\Repository;

use Mosparo\Entity\SecurityGuideline;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method SecurityGuideline|null find($id, $lockMode = null, $lockVersion = null)
 * @method SecurityGuideline|null findOneBy(array $criteria, array $orderBy = null)
 * @method SecurityGuideline[]    findAll()
 * @method SecurityGuideline[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SecurityGuidelineRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SecurityGuideline::class);
    }

    // /**
    //  * @return SecurityGuideline[] Returns an array of SecurityGuideline objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('sg')
            ->andWhere('sg.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('sg.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?SecurityGuideline
    {
        return $this->createQueryBuilder('sg')
            ->andWhere('sg.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
