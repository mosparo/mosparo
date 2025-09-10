<?php

namespace Mosparo\Repository;

use Mosparo\Entity\CleanupStatistic;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method CleanupStatistic|null find($id, $lockMode = null, $lockVersion = null)
 * @method CleanupStatistic|null findOneBy(array $criteria, array $orderBy = null)
 * @method CleanupStatistic[]    findAll()
 * @method CleanupStatistic[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CleanupStatisticRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CleanupStatistic::class);
    }

    // /**
    //  * @return CleanupStatistic[] Returns an array of CleanupStatistic objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('ds')
            ->andWhere('ds.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('ds.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?CleanupStatistic
    {
        return $this->createQueryBuilder('ds')
            ->andWhere('ds.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
