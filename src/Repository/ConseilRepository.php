<?php

namespace App\Repository;

use App\Entity\Conseil;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Conseil>
 */
class ConseilRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Conseil::class);
    }

    //    /**
    //     * @return Conseil[] Returns an array of Conseil objects
    //     */
    public function findByMoisNumber(int $moisNumber): array
{
    return $this->createQueryBuilder('c')
        ->join('c.mois', 'm')
        ->where('m.number = :num')
        ->setParameter('num', $moisNumber)
        ->getQuery()
        ->getResult();
}
}
