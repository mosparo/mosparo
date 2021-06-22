<?php

namespace Mosparo\Repository;

use Mosparo\Entity\SubmitToken;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method SubmitToken|null find($id, $lockMode = null, $lockVersion = null)
 * @method SubmitToken|null findOneBy(array $criteria, array $orderBy = null)
 * @method SubmitToken[]    findAll()
 * @method SubmitToken[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SubmitTokenRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SubmitToken::class);
    }

    // /**
    //  * @return SubmitToken[] Returns an array of SubmitToken objects
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
    public function findOneBySomeField($value): ?SubmitToken
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
