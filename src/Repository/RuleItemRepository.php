<?php

namespace Mosparo\Repository;

use Mosparo\Entity\RuleItem;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method RuleItem|null find($id, $lockMode = null, $lockVersion = null)
 * @method RuleItem|null findOneBy(array $criteria, array $orderBy = null)
 * @method RuleItem[]    findAll()
 * @method RuleItem[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class RuleItemRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RuleItem::class);
    }

    // /**
    //  * @return RuleItem[] Returns an array of RuleItem objects
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
    public function findOneBySomeField($value): ?RuleItem
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
