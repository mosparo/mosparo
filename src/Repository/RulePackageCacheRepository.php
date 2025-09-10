<?php

namespace Mosparo\Repository;

use Mosparo\Entity\RulePackageCache;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method RulePackageCache|null find($id, $lockMode = null, $lockVersion = null)
 * @method RulePackageCache|null findOneBy(array $criteria, array $orderBy = null)
 * @method RulePackageCache[]    findAll()
 * @method RulePackageCache[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class RulePackageCacheRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RulePackageCache::class);
    }

    // /**
    //  * @return RulePackageCache[] Returns an array of RulePackageCache objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('r.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?RulePackageCache
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
