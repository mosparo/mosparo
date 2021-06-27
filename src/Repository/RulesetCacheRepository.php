<?php

namespace Mosparo\Repository;

use Mosparo\Entity\RulesetCache;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method RulesetCache|null find($id, $lockMode = null, $lockVersion = null)
 * @method RulesetCache|null findOneBy(array $criteria, array $orderBy = null)
 * @method RulesetCache[]    findAll()
 * @method RulesetCache[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class RulesetCacheRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RulesetCache::class);
    }

    // /**
    //  * @return RulesetCache[] Returns an array of RulesetCache objects
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
    public function findOneBySomeField($value): ?RulesetCache
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
