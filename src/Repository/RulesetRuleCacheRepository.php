<?php

namespace Mosparo\Repository;

use Mosparo\Entity\RulesetRuleCache;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method RulesetRuleCache|null find($id, $lockMode = null, $lockVersion = null)
 * @method RulesetRuleCache|null findOneBy(array $criteria, array $orderBy = null)
 * @method RulesetRuleCache[]    findAll()
 * @method RulesetRuleCache[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class RulesetRuleCacheRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RulesetRuleCache::class);
    }

    // /**
    //  * @return RulesetRuleCache[] Returns an array of RulesetRuleCache objects
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
    public function findOneBySomeField($value): ?RulesetRuleCache
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
