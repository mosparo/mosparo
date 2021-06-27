<?php

namespace Mosparo\Repository;

use Mosparo\Entity\IpLocalization;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method IpLocalization|null find($id, $lockMode = null, $lockVersion = null)
 * @method IpLocalization|null findOneBy(array $criteria, array $orderBy = null)
 * @method IpLocalization[]    findAll()
 * @method IpLocalization[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class IpLocalizationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, IpLocalization::class);
    }

    // /**
    //  * @return IpLocalization[] Returns an array of IpLocalization objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('i')
            ->andWhere('i.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('i.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?IpLocalization
    {
        return $this->createQueryBuilder('i')
            ->andWhere('i.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
