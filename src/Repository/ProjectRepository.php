<?php

namespace Mosparo\Repository;

use Mosparo\Entity\Project;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Project|null find($id, $lockMode = null, $lockVersion = null)
 * @method Project|null findOneBy(array $criteria, array $orderBy = null)
 * @method Project[]    findAll()
 * @method Project[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ProjectRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Project::class);
    }

    /**
     * Returns the project with the given numeric ID or with the given UUID, so that a command can address a
     * project by the UUID which mosparo:project:create prints and which an automated deployment knows.
     */
    public function findOneByIdOrUuid(string $identifier): ?Project
    {
        if (uuid_is_valid($identifier)) {
            return $this->findOneBy(['uuid' => $identifier]);
        }

        // The ID is the primary key, so everything which is not a number cannot be found and would only
        // make Doctrine convert the value.
        if (!ctype_digit($identifier)) {
            return null;
        }

        return $this->find((int) $identifier);
    }

    // /**
    //  * @return Project[] Returns an array of Project objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('p.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Project
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
