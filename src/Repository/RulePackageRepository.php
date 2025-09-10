<?php

namespace Mosparo\Repository;

use Mosparo\Entity\RulePackage;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method RulePackage|null find($id, $lockMode = null, $lockVersion = null)
 * @method RulePackage|null findOneBy(array $criteria, array $orderBy = null)
 * @method RulePackage[]    findAll()
 * @method RulePackage[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class RulePackageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RulePackage::class);
    }

    // /**
    //  * @return RulePackage[] Returns an array of RulePackage objects
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
    public function findOneBySomeField($value): ?RulePackage
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
