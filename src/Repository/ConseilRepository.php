<?php

namespace App\Repository;

use App\Entity\Conseil;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Conseil>
 *
 * Repository de l'entité Conseil.
 * Contient des méthodes de requêtes personnalisées pour récupérer
 * les conseils en fonction de leurs mois associés.
 */
class ConseilRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Conseil::class);
    }

    /**
     * Retourne tous les conseils associés à un numéro de mois donné.
     *
     * Cette méthode effectue un JOIN sur la relation ManyToMany entre Conseil et Mois
     * et filtre sur la propriété number de l'entité Mois.
     *
     * @param int $moisNumber Numéro du mois (1 à 12).
     *
     * @return Conseil[] Liste des conseils trouvés pour ce mois.
     */
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
