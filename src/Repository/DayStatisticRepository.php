<?php

namespace Mosparo\Repository;

use Mosparo\Entity\DayStatistic;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method DayStatistic|null find($id, $lockMode = null, $lockVersion = null)
 * @method DayStatistic|null findOneBy(array $criteria, array $orderBy = null)
 * @method DayStatistic[]    findAll()
 * @method DayStatistic[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class DayStatisticRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DayStatistic::class);
    }

    // /**
    //  * @return DayStatistic[] Returns an array of DayStatistic objects
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
    public function findOneBySomeField($value): ?DayStatistic
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
