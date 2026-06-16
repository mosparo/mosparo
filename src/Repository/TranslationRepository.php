<?php

namespace Mosparo\Repository;

use Mosparo\Entity\Translation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Translation|null find($id, $lockMode = null, $lockVersion = null)
 * @method Translation|null findOneBy(array $criteria, array $orderBy = null)
 * @method Translation[]    findAll()
 * @method Translation[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TranslationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Translation::class);
    }

    // /**
    //  * @return Translation[] Returns an array of Translation objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('sg')
            ->andWhere('sg.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('sg.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Translation
    {
        return $this->createQueryBuilder('sg')
            ->andWhere('sg.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
