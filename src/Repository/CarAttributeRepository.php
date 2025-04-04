<?php

namespace App\Repository;

use App\Entity\CarAttribute;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method CarAttribute|null find($id, $lockMode = null, $lockVersion = null)
 * @method CarAttribute|null findOneBy(array $criteria, array $orderBy = null)
 * @method CarAttribute[]    findAll()
 * @method CarAttribute[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CarAttributeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CarAttribute::class);
    }

    // /**
    //  * @return CarAttribute[] Returns an array of CarAttribute objects
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
    public function findOneBySomeField($value): ?CarAttribute
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
