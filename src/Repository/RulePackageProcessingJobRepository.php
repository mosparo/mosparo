<?php

namespace Mosparo\Repository;

use Mosparo\Entity\RulePackageProcessingJob;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method RulePackageProcessingJob|null find($id, $lockMode = null, $lockVersion = null)
 * @method RulePackageProcessingJob|null findOneBy(array $criteria, array $orderBy = null)
 * @method RulePackageProcessingJob[]    findAll()
 * @method RulePackageProcessingJob[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class RulePackageProcessingJobRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RulePackageProcessingJob::class);
    }

    // /**
    //  * @return RulePackageProcessingJob[] Returns an array of RulePackageProcessingJob objects
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
    public function findOneBySomeField($value): ?RulePackageProcessingJob
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
