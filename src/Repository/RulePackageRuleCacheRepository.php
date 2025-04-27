<?php

namespace Mosparo\Repository;

use Mosparo\Entity\RulePackageRuleCache;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method RulePackageRuleCache|null find($id, $lockMode = null, $lockVersion = null)
 * @method RulePackageRuleCache|null findOneBy(array $criteria, array $orderBy = null)
 * @method RulePackageRuleCache[]    findAll()
 * @method RulePackageRuleCache[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class RulePackageRuleCacheRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RulePackageRuleCache::class);
    }

    // /**
    //  * @return RulePackageRuleCache[] Returns an array of RulePackageRuleCache objects
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
    public function findOneBySomeField($value): ?RulePackageRuleCache
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
