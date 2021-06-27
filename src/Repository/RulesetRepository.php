<?php

namespace Mosparo\Repository;

use Mosparo\Entity\Ruleset;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Ruleset|null find($id, $lockMode = null, $lockVersion = null)
 * @method Ruleset|null findOneBy(array $criteria, array $orderBy = null)
 * @method Ruleset[]    findAll()
 * @method Ruleset[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class RulesetRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Ruleset::class);
    }

    // /**
    //  * @return Ruleset[] Returns an array of Ruleset objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('s.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Ruleset
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
