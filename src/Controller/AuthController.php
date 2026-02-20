<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;

/**
 * Contrôleur d'authentification.
 *
 * Il expose un endpoint permettant de vérifier les identifiants
 * d'un utilisateur et de générer un jeton JWT en cas de succès.
 */
#[Route('/auth', name: 'auth_')]
class AuthController extends AbstractController
{
    /**
     * Authentifie un utilisateur et retourne un jeton JWT.
     *
     * Corps attendu (JSON) :
     *  - email : string
     *  - password : string
     *
     * @param Request                     $request       Requête HTTP contenant le JSON avec email / password.
     * @param EntityManagerInterface      $em            Permet de récupérer le repository User.
     * @param JWTTokenManagerInterface    $jwtManager    Service Lexik qui génère le jeton JWT.
     * @param UserPasswordHasherInterface $passwordHasher Service pour vérifier le mot de passe hashé.
     *
     * @return JsonResponse Retourne soit le token, soit un message d'erreur avec le code HTTP approprié.
     */
    #[Route('', name: 'login', methods: ['POST'])]
    public function login(
        Request $request,
        EntityManagerInterface $em,
        JWTTokenManagerInterface $jwtManager,
        UserPasswordHasherInterface $passwordHasher
    ): JsonResponse
    {
        // On décode le JSON envoyé par le client (Postman, front, ...)
        $data = json_decode($request->getContent(), true);

        // Vérification minimale de la présence des champs nécessaires
        if (empty($data['email']) || empty($data['password'])) {
            return $this->json(['error' => 'Email et mot de passe obligatoires'], 400);
        }

        // On récupère le repository via l'EntityManager
        $user = $em->getRepository(User::class)
                   ->findOneBy(['email' => $data['email']]);

        if (!$user) {
            return $this->json(['error' => 'Utilisateur non trouvé'], 401);
        }

        if (!$passwordHasher->isPasswordValid($user, $data['password'])) {
            return $this->json(['error' => 'Mot de passe incorrect'], 401);
        }

        $token = $jwtManager->create($user);

        return $this->json([
            'token' => $token
        ]);
    }
}