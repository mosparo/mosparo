<?php

namespace Mosparo\Repository;

use Mosparo\Entity\Delay;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Delay|null find($id, $lockMode = null, $lockVersion = null)
 * @method Delay|null findOneBy(array $criteria, array $orderBy = null)
 * @method Delay[]    findAll()
 * @method Delay[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class DelayRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Delay::class);
    }

    // /**
    //  * @return Delay[] Returns an array of Delay objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('d')
            ->andWhere('d.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('d.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Delay
    {
        return $this->createQueryBuilder('d')
            ->andWhere('d.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
