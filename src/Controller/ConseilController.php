<?php

namespace App\Controller;

use App\Repository\ConseilRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/conseil', name: 'conseil_')]
class ConseilController extends AbstractController
{
    // Route pour tous les conseils
// Route pour le mois en cours
#[Route('', name: 'current_month', methods: ['GET'])]
public function getCurrentMonth(ConseilRepository $conseilRepository): JsonResponse
{
    $currentMonth = (int) date('n');

    $conseils = $conseilRepository->findByMoisNumber($currentMonth);

    if (empty($conseils)) {
        return new JsonResponse(null, 204);
    }

    $data = array_map(function($conseil) use ($currentMonth) {
        $moisList = array_filter($conseil->getMois()->toArray(), fn($m) => $m->getNumber() == $currentMonth);

        return [
            'id' => $conseil->getId(),
            'content' => $conseil->getContent(),
            'createdAt' => $conseil->getCreatedAt()?->format('Y-m-d H:i:s'),
            'updatedAt' => $conseil->getUpdatedAt()?->format('Y-m-d H:i:s'),
            'mois' => array_map(fn($m) => [
                'id' => $m->getId(),
                'number' => $m->getNumber(),
                'name' => $m->getName(),
            ], $moisList)
        ];
    }, $conseils);

    return $this->json($data);
}

    // Route pour un mois précis (1 à 12)
    #[Route('/{mois}', name: 'by_month', methods: ['GET'], requirements: ['mois' => '[1-9]|1[0-2]'])]
    public function getByMonth(int $mois, ConseilRepository $conseilRepository): JsonResponse
    {
        $conseils = $conseilRepository->findByMoisNumber($mois);

        // Si aucun conseil pour ce mois, renvoyer 204 No Content
        if (empty($conseils)) {
            return new JsonResponse(null, 204);
        }

        $data = array_map(function($conseil) use ($mois) {
            // Ne garder que le mois filtré
            $moisList = array_filter($conseil->getMois()->toArray(), fn($m) => $m->getNumber() == $mois);

            return [
                'id' => $conseil->getId(),
                'content' => $conseil->getContent(),
                'createdAt' => $conseil->getCreatedAt()?->format('Y-m-d H:i:s'),
                'updatedAt' => $conseil->getUpdatedAt()?->format('Y-m-d H:i:s'),
                'mois' => array_map(fn($m) => [
                    'id' => $m->getId(),
                    'number' => $m->getNumber(),
                    'name' => $m->getName(),
                ], $moisList)
            ];
        }, $conseils);

        return $this->json($data);
    }
}