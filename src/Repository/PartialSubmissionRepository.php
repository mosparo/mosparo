<?php

namespace Mosparo\Repository;

use Mosparo\Entity\PartialSubmission;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method PartialSubmission|null find($id, $lockMode = null, $lockVersion = null)
 * @method PartialSubmission|null findOneBy(array $criteria, array $orderBy = null)
 * @method PartialSubmission[]    findAll()
 * @method PartialSubmission[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PartialSubmissionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PartialSubmission::class);
    }

    // /**
    //  * @return PartialSubmission[] Returns an array of PartialSubmission objects
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
    public function findOneBySomeField($value): ?PartialSubmission
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
