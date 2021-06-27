<?php

namespace Mosparo\Repository;

use Mosparo\Entity\ConfigValue;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method ConfigValue|null find($id, $lockMode = null, $lockVersion = null)
 * @method ConfigValue|null findOneBy(array $criteria, array $orderBy = null)
 * @method ConfigValue[]    findAll()
 * @method ConfigValue[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ConfigValueRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ConfigValue::class);
    }

    // /**
    //  * @return ConfigValue[] Returns an array of ConfigValue objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('c.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?ConfigValue
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
