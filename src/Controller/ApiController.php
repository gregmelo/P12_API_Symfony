<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Contrôleur de base de l'API.
 *
 * Pour l'instant il expose seulement la racine de l'API,
 * utile pour vérifier rapidement que le backend répond.
 */
class ApiController extends AbstractController
{
    /**
     * Point d'entrée racine de l'API.
     *
     * @return JsonResponse Message simple de bienvenue permettant de tester que l'API est joignable.
     */
    #[Route('/', name: 'api_root', methods: ['GET'])]
    public function index(): JsonResponse
    {
        return new JsonResponse([
            'message' => 'API EcoGarden & co',
        ]);
    }
}
