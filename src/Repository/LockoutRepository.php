<?php

namespace Mosparo\Repository;

use Mosparo\Entity\Lockout;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Lockout|null find($id, $lockMode = null, $lockVersion = null)
 * @method Lockout|null findOneBy(array $criteria, array $orderBy = null)
 * @method Lockout[]    findAll()
 * @method Lockout[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class LockoutRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Lockout::class);
    }

    // /**
    //  * @return Lockout[] Returns an array of Lockout objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('l')
            ->andWhere('l.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('l.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Lockout
    {
        return $this->createQueryBuilder('l')
            ->andWhere('l.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
