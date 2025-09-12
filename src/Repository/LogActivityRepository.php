<?php

namespace App\Repository;

use App\Entity\LogActivity;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<LogActivity>
 */
class LogActivityRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LogActivity::class);
    }

    //    /**
    //     * @return LogActivity[] Returns an array of LogActivity objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('l')
    //            ->andWhere('l.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('l.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?LogActivity
    //    {
    //        return $this->createQueryBuilder('l')
    //            ->andWhere('l.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
