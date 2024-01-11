<?php

namespace Mosparo\Repository;

use Mosparo\Entity\SecurityGuidelineConfigValue;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method SecurityGuidelineConfigValue|null find($id, $lockMode = null, $lockVersion = null)
 * @method SecurityGuidelineConfigValue|null findOneBy(array $criteria, array $orderBy = null)
 * @method SecurityGuidelineConfigValue[]    findAll()
 * @method SecurityGuidelineConfigValue[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SecurityGuidelineConfigValueRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SecurityGuidelineConfigValue::class);
    }

    // /**
    //  * @return SecurityGuidelineConfigValue[] Returns an array of SecurityGuidelineConfigValue objects
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
    public function findOneBySomeField($value): ?SecurityGuidelineConfigValue
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
