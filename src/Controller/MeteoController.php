<?php

namespace App\Controller;

use App\Service\MeteoService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Contrôleur pour l'API météo.
 *
 * Il expose deux endpoints :
 *  - GET /meteo/{ville} pour demander la météo d'une ville précise
 *  - GET /meteo pour demander la météo de la ville de l'utilisateur connecté.
 *
 * Accessible aux utilisateurs authentifiés (ROLE_USER ou ROLE_ADMIN)
 * via la configuration de sécurité existante.
 */
#[Route('/meteo', name: 'meteo_')]
class MeteoController extends AbstractController
{
    /**
     * @param MeteoService $meteoService Service métier chargé d'appeler l'API météo externe.
     */
    public function __construct(private MeteoService $meteoService)
    {
    }

    /**
     * Retourne la météo pour une ville donnée passée dans l'URL.
     *
     * Exemple : GET /meteo/Lyon
     *
     * @param string $ville Nom de la ville passée dans l'URL.
     *
     * @return JsonResponse Données météo simplifiées ou erreur si la ville est introuvable.
     */
    #[Route('/{ville}', name: 'by_city', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function getByCity(string $ville): JsonResponse
    {
        // On délègue au service la récupération/caching de la météo pour cette ville
        $weather = $this->meteoService->getWeatherForCity($ville);

        if ($weather === null) {
            return $this->json([
                'error' => sprintf('Ville "%s" introuvable pour l\'API météo', $ville),
            ], 404);
        }

        return $this->json($weather);
    }

    /**
     * Retourne la météo pour la ville de l'utilisateur connecté.
     *
     * Exemple : GET /meteo
     *
     * @param UserInterface $user Utilisateur actuellement authentifié (doit exposer getCity()).
     *
     * @return JsonResponse Données météo simplifiées ou erreur si la ville n'est pas disponible.
     */
    #[Route('', name: 'by_user_city', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function getForCurrentUser(UserInterface $user): JsonResponse
    {
        // On vérifie que l'implémentation de User possède bien une méthode getCity()
        if (!method_exists($user, 'getCity')) {
            return $this->json([
                'error' => 'Impossible de déterminer la ville de l\'utilisateur.'
            ], 400);
        }

        /** @var \App\Entity\User $user */
        $weather = $this->meteoService->getWeatherForUser($user);

        if ($weather === null) {
            return $this->json([
                'error' => 'Ville de l\'utilisateur introuvable ou non reconnue par l\'API météo.'
            ], 404);
        }

        return $this->json($weather);
    }
}
