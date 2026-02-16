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

#[Route('/conseil', name: 'conseil_')]
class ConseilController extends AbstractController
{
    // GET /conseil : conseils pour le mois en cours
    #[Route('', name: 'current_month', methods: ['GET'])]
    public function getCurrentMonth(ConseilRepository $conseilRepository): JsonResponse
    {
        $currentMonth = (int) date('n');

        $conseils = $conseilRepository->findByMoisNumber($currentMonth);

        if (empty($conseils)) {
            return new JsonResponse(null, 204);
        }

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

    // GET /conseil/{mois} : conseils pour un mois précis
    #[Route('/{mois}', name: 'by_month', methods: ['GET'], requirements: ['mois' => '\d+'])]
    public function getByMonth(int $mois, ConseilRepository $conseilRepository): JsonResponse
    {
        if ($mois < 1 || $mois > 12) {
            return $this->json(['error' => 'Mois invalide (doit être entre 1 et 12)'], 400);
        }

        $conseils = $conseilRepository->findByMoisNumber($mois);

        if (empty($conseils)) {
            return new JsonResponse(null, 204);
        }

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

    // POST /conseil : créer un conseil (admin)
    #[Route('', name: 'create', methods: ['POST'])]
    public function create(
        Request $request,
        EntityManagerInterface $em,
        MoisRepository $moisRepository
    ): JsonResponse {
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

    // PUT /conseil/{id} : mise à jour partielle d'un conseil (admin)
    #[Route('/{id}', name: 'update', methods: ['PUT'])]
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

        $data = json_decode($request->getContent(), true);
        if (!is_array($data)) {
            return $this->json(['error' => 'Corps de requête invalide'], 400);
        }

        if (array_key_exists('content', $data)) {
            if (!is_string($data['content']) || trim($data['content']) === '') {
                return $this->json(['error' => 'Le champ "content" ne peut pas être vide'], 400);
            }
            $conseil->setContent($data['content']);
        }

        if (array_key_exists('months', $data)) {
            if (!is_array($data['months']) || empty($data['months'])) {
                return $this->json(['error' => '"months" doit être une liste non vide de mois'], 400);
            }

            foreach ($conseil->getMois() as $existingMois) {
                $conseil->removeMois($existingMois);
            }

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

        return $this->json(['message' => 'Conseil supprimé avec succès'], 204);
    }
}