<?php

namespace Mosparo\Repository;

use Mosparo\Entity\SubmissionRuleConfigValue;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method SubmissionRuleConfigValue|null find($id, $lockMode = null, $lockVersion = null)
 * @method SubmissionRuleConfigValue|null findOneBy(array $criteria, array $orderBy = null)
 * @method SubmissionRuleConfigValue[]    findAll()
 * @method SubmissionRuleConfigValue[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SubmissionRuleConfigValueRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SubmissionRuleConfigValue::class);
    }

    // /**
    //  * @return SubmissionRuleConfigValue[] Returns an array of SubmissionRuleConfigValue objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('sgcv')
            ->andWhere('sgcv.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('sgcv.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?SubmissionRuleConfigValue
    {
        return $this->createQueryBuilder('sgcv')
            ->andWhere('sgcv.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
