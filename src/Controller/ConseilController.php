<?php

namespace App\Controller;

use App\Repository\ConseilRepository;
use App\Repository\MoisRepository;
use App\Entity\Conseil;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Contrôleur d'exposition et de gestion des conseils.
 *
 * Il permet de :
 *  - récupérer les conseils du mois courant ou d'un mois donné
 *  - créer, mettre à jour et supprimer des conseils (opérations d'admin).
 */
#[Route('/conseil', name: 'conseil_')]
class ConseilController extends AbstractController
{
    /**
     * Retourne la liste des conseils associés au mois courant.
     *
     * Le mois courant est déterminé à partir de la date serveur (PHP date('n')).
     * Retourne 204 si aucun conseil n'est trouvé.
     *
     * @param ConseilRepository $conseilRepository Repository permettant de charger les conseils.
     *
     * @return JsonResponse Liste de conseils ou 204 No Content.
     */
    // GET /conseil : conseils pour le mois en cours
    #[Route('', name: 'current_month', methods: ['GET'])]
    public function getCurrentMonth(ConseilRepository $conseilRepository): JsonResponse
    {
        $currentMonth = (int) date('n');

        // Récupère tous les conseils liés à ce numéro de mois
        $conseils = $conseilRepository->findByMoisNumber($currentMonth);

        if (empty($conseils)) {
            return new JsonResponse(null, 204);
        }

        // Transformation des entités en tableau associatif prêt à être sérialisé en JSON
        $data = array_map(function (Conseil $conseil) use ($currentMonth) {
            $moisList = array_filter(
                $conseil->getMois()->toArray(),
                fn($m) => $m->getNumber() === $currentMonth
            );

            return [
                'id' => $conseil->getId(),
                'content' => $conseil->getContent(),
                'createdAt' => $conseil->getCreatedAt()?->format('Y-m-d H:i:s'),
                'updatedAt' => $conseil->getUpdatedAt()?->format('Y-m-d H:i:s'),
                'mois' => array_map(fn($m) => [
                    'id' => $m->getId(),
                    'number' => $m->getNumber(),
                    'name' => $m->getName(),
                ], $moisList),
            ];
        }, $conseils);

        return $this->json($data);
    }

    /**
     * Retourne les conseils pour un mois précis (1 à 12).
     *
     * @param int               $mois              Numéro de mois demandé (1 = janvier, 12 = décembre).
     * @param ConseilRepository $conseilRepository Repository permettant de charger les conseils.
     *
     * @return JsonResponse Liste de conseils pour ce mois ou 204/400.
     */
    // GET /conseil/{mois} : conseils pour un mois précis
    #[Route('/{mois}', name: 'by_month', methods: ['GET'], requirements: ['mois' => '\d+'])]
    public function getByMonth(int $mois, ConseilRepository $conseilRepository): JsonResponse
    {
        if ($mois < 1 || $mois > 12) {
            return $this->json(['error' => 'Mois invalide (doit être entre 1 et 12)'], 400);
        }

        // Récupère les conseils liés au mois passé en paramètre
        $conseils = $conseilRepository->findByMoisNumber($mois);

        if (empty($conseils)) {
            return new JsonResponse(null, 204);
        }

        // Transformation des entités en structure de données simple pour le JSON
        $data = array_map(function (Conseil $conseil) use ($mois) {
            $moisList = array_filter(
                $conseil->getMois()->toArray(),
                fn($m) => $m->getNumber() === $mois
            );

            return [
                'id' => $conseil->getId(),
                'content' => $conseil->getContent(),
                'createdAt' => $conseil->getCreatedAt()?->format('Y-m-d H:i:s'),
                'updatedAt' => $conseil->getUpdatedAt()?->format('Y-m-d H:i:s'),
                'mois' => array_map(fn($m) => [
                    'id' => $m->getId(),
                    'number' => $m->getNumber(),
                    'name' => $m->getName(),
                ], $moisList),
            ];
        }, $conseils);

        return $this->json($data);
    }

    /**
     * Crée un nouveau conseil et l'associe à un ou plusieurs mois.
     *
     * Corps attendu (JSON) :
     *  - content : string
     *  - months  : int[] (liste de numéros de mois entre 1 et 12)
     *
     * @param Request                $request       Requête HTTP contenant le JSON.
     * @param EntityManagerInterface $em            Gestionnaire d'entités Doctrine.
     * @param MoisRepository         $moisRepository Repository pour vérifier/charger les mois.
     *
     * @return JsonResponse Détail du conseil créé ou message d'erreur.
     */
    // POST /conseil : créer un conseil (admin)
    #[Route('', name: 'create', methods: ['POST'])]
    public function create(
        Request $request,
        EntityManagerInterface $em,
        MoisRepository $moisRepository
    ): JsonResponse {
        // Décodage du JSON et validation de base sur la structure
        $data = json_decode($request->getContent(), true);

        if (!is_array($data) || empty($data['content']) || empty($data['months']) || !is_array($data['months'])) {
            return $this->json([
                'error' => 'Champs "content" et "months" (liste de mois 1-12) obligatoires',
            ], 400);
        }

        $conseil = new Conseil();
        $conseil->setContent($data['content']);
        $conseil->setCreatedAt(new \DateTimeImmutable());
        $conseil->setUpdatedAt(new \DateTimeImmutable());

        // Pour chaque numéro de mois, on vérifie sa validité et on récupère l'entité correspondante
        foreach ($data['months'] as $monthNumber) {
            if (!is_int($monthNumber)) {
                $monthNumber = (int) $monthNumber;
            }

            if ($monthNumber < 1 || $monthNumber > 12) {
                return $this->json([
                    'error' => sprintf('Mois invalide dans la liste: %d (doit être entre 1 et 12)', $monthNumber),
                ], 400);
            }

            $mois = $moisRepository->findOneBy(['number' => $monthNumber]);
            if (!$mois) {
                return $this->json([
                    'error' => sprintf('Mois %d introuvable en base', $monthNumber),
                ], 400);
            }

            $conseil->addMois($mois);
        }

        $em->persist($conseil);
        $em->flush();

        return $this->json([
            'id' => $conseil->getId(),
            'content' => $conseil->getContent(),
            'createdAt' => $conseil->getCreatedAt()?->format('Y-m-d H:i:s'),
            'updatedAt' => $conseil->getUpdatedAt()?->format('Y-m-d H:i:s'),
            'mois' => array_map(fn($m) => [
                'id' => $m->getId(),
                'number' => $m->getNumber(),
                'name' => $m->getName(),
            ], $conseil->getMois()->toArray()),
        ], 201);
    }

    /**
     * Met à jour partiellement un conseil existant.
     *
     * On peut modifier le contenu et/ou la liste des mois associés.
     *
     * @param int                   $id               Identifiant du conseil à modifier.
     * @param Request               $request          Requête HTTP avec le JSON partiel.
     * @param EntityManagerInterface $em              Gestionnaire d'entités Doctrine.
     * @param ConseilRepository     $conseilRepository Repository de conseils.
     * @param MoisRepository        $moisRepository   Permet de charger les entités Mois.
     *
     * @return JsonResponse Détail du conseil mis à jour ou erreur.
     */
    // PUT/PATCH /conseil/{id} : mise à jour partielle d'un conseil (admin)
    #[Route('/{id}', name: 'update', methods: ['PUT', 'PATCH'])]
    public function update(
        int $id,
        Request $request,
        EntityManagerInterface $em,
        ConseilRepository $conseilRepository,
        MoisRepository $moisRepository
    ): JsonResponse {
        $conseil = $conseilRepository->find($id);
        if (!$conseil) {
            return $this->json(['error' => 'Conseil non trouvé'], 404);
        }

        // On lit le JSON pour savoir quels champs sont à modifier
        $data = json_decode($request->getContent(), true);
        if (!is_array($data)) {
            return $this->json(['error' => 'Corps de requête invalide'], 400);
        }

        // Si le contenu est fourni, on le met à jour après validation
        if (array_key_exists('content', $data)) {
            if (!is_string($data['content']) || trim($data['content']) === '') {
                return $this->json(['error' => 'Le champ "content" ne peut pas être vide'], 400);
            }
            $conseil->setContent($data['content']);
        }

        // Si la liste des mois est fournie, on remplace l'association existante
        if (array_key_exists('months', $data)) {
            if (!is_array($data['months']) || empty($data['months'])) {
                return $this->json(['error' => '"months" doit être une liste non vide de mois'], 400);
            }

            // On supprime toutes les associations existantes
            foreach ($conseil->getMois() as $existingMois) {
                $conseil->removeMois($existingMois);
            }

            // Puis on ajoute chaque mois valide passé dans la requête
            foreach ($data['months'] as $monthNumber) {
                if (!is_int($monthNumber)) {
                    $monthNumber = (int) $monthNumber;
                }

                if ($monthNumber < 1 || $monthNumber > 12) {
                    return $this->json([
                        'error' => sprintf('Mois invalide dans la liste: %d (doit être entre 1 et 12)', $monthNumber),
                    ], 400);
                }

                $mois = $moisRepository->findOneBy(['number' => $monthNumber]);
                if (!$mois) {
                    return $this->json([
                        'error' => sprintf('Mois %d introuvable en base', $monthNumber),
                    ], 400);
                }

                $conseil->addMois($mois);
            }
        }

        $conseil->setUpdatedAt(new \DateTimeImmutable());
        $em->flush();

        return $this->json([
            'id' => $conseil->getId(),
            'content' => $conseil->getContent(),
            'createdAt' => $conseil->getCreatedAt()?->format('Y-m-d H:i:s'),
            'updatedAt' => $conseil->getUpdatedAt()?->format('Y-m-d H:i:s'),
            'mois' => array_map(fn($m) => [
                'id' => $m->getId(),
                'number' => $m->getNumber(),
                'name' => $m->getName(),
            ], $conseil->getMois()->toArray()),
        ], 200);
    }

    /**
     * Supprime un conseil existant.
     *
     * @param int                    $id               Identifiant du conseil à supprimer.
     * @param EntityManagerInterface $em               Gestionnaire d'entités Doctrine.
     * @param ConseilRepository      $conseilRepository Repository des conseils.
     *
     * @return JsonResponse Réponse vide avec statut 204 ou erreur si le conseil n'existe pas.
     */
    // DELETE /conseil/{id} : suppression d'un conseil (admin)
    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    public function delete(int $id, EntityManagerInterface $em, ConseilRepository $conseilRepository): JsonResponse
    {
        $conseil = $conseilRepository->find($id);
        if (!$conseil) {
            return $this->json(['error' => 'Conseil non trouvé'], 404);
        }

        $em->remove($conseil);
        $em->flush();

        // 204 No Content : on ne renvoie volontairement aucun corps
        return new JsonResponse(null, 204);
    }
}