<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use App\Repository\UserRepository;

/**
 * Contrôleur de gestion des utilisateurs.
 *
 * Il permet de créer un utilisateur (inscription publique),
 * puis de le mettre à jour ou de le supprimer (opérations réservées à l'admin via la sécurité).
 */
#[Route('/user', name: 'user_')]
class UserController extends AbstractController
{
    /**
     * Crée un nouvel utilisateur à partir des données JSON envoyées.
     *
     * Corps attendu (JSON) :
     *  - email : string (unique)
     *  - password : string
     *  - city : string
     *
     * @param Request                     $request        Requête HTTP avec le corps JSON.
     * @param EntityManagerInterface      $em             Gestionnaire d'entités Doctrine.
     * @param UserPasswordHasherInterface $passwordHasher Service de hash du mot de passe.
     * @param UserRepository              $userRepository Repository utilisé pour vérifier l'unicité de l'email.
     *
     * @return JsonResponse Confirmation de création ou message d'erreur.
     */
    #[Route('', name: 'create', methods: ['POST'])]
    public function create(
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $passwordHasher,
        UserRepository $userRepository
    ): JsonResponse {
        // Décodage du JSON venant du client
        $data = json_decode($request->getContent(), true);

        // Validation minimale des champs requis
        if (empty($data['email']) || empty($data['password']) || empty($data['city'])) {
            return $this->json(['error' => 'Email, mot de passe et ville sont obligatoires'], 400);
        }

        // Vérifie si l'utilisateur existe déjà en base via son email
        if ($userRepository->findOneBy(['email' => $data['email']])) {
        return $this->json(['error' => 'Cet email est déjà utilisé'], 409);
        }

        // Création et hydratation de l'utilisateur
        $user = new User();
        $user->setEmail($data['email']);
        $user->setCity($data['city']);
        $user->setCreatedAt(new \DateTimeImmutable());

        // Hash du mot de passe avant de le stocker en base
        $hashedPassword = $passwordHasher->hashPassword($user, $data['password']);
        $user->setPassword($hashedPassword);

        $em->persist($user);
        $em->flush();

        return $this->json([
            'message' => 'Utilisateur créé avec succès',
            'id' => $user->getId()
        ], 201);
    }

    /**
     * Met à jour partiellement un utilisateur existant.
     *
     * Tous les champs sont optionnels, on ne modifie que ceux présents
     * dans le JSON : email, password, city.
     *
     * @param int                         $id             Identifiant de l'utilisateur à modifier.
     * @param Request                     $request        Requête HTTP contenant le JSON partiel.
     * @param EntityManagerInterface      $em             Gestionnaire d'entités Doctrine.
     * @param UserRepository              $userRepository Permet de charger l'utilisateur et vérifier l'unicité email.
     * @param UserPasswordHasherInterface $passwordHasher Service pour re‑hasher un nouveau mot de passe.
     *
     * @return JsonResponse Détail de l'utilisateur mis à jour ou erreur.
     */
    #[Route('/{id}', name: 'update', methods: ['PUT'])]
    public function update(
        int $id,
        Request $request,
        EntityManagerInterface $em,
        UserRepository $userRepository,
        UserPasswordHasherInterface $passwordHasher
    ): JsonResponse {
        $user = $userRepository->find($id);
        if (!$user) {
            return $this->json(['error' => 'Utilisateur non trouvé'], 404);
        }

        // On décode le JSON envoyé pour savoir quels champs mettre à jour
        $data = json_decode($request->getContent(), true);
        if (!is_array($data)) {
            return $this->json(['error' => 'Corps de requête invalide'], 400);
        }

        // Mise à jour éventuelle de l'email, avec contrôle d'unicité
        if (array_key_exists('email', $data)) {
            if (empty($data['email'])) {
                return $this->json(['error' => 'Email ne peut pas être vide'], 400);
            }

            $existing = $userRepository->findOneBy(['email' => $data['email']]);
            if ($existing && $existing->getId() !== $user->getId()) {
                return $this->json(['error' => 'Cet email est déjà utilisé'], 409);
            }

            $user->setEmail($data['email']);
        }

        // Mise à jour éventuelle de la ville
        if (array_key_exists('city', $data)) {
            if (empty($data['city'])) {
                return $this->json(['error' => 'La ville ne peut pas être vide'], 400);
            }
            $user->setCity($data['city']);
        }

        // Mise à jour éventuelle du mot de passe (avec re‑hash)
        if (array_key_exists('password', $data)) {
            if (empty($data['password'])) {
                return $this->json(['error' => 'Le mot de passe ne peut pas être vide'], 400);
            }
            $hashedPassword = $passwordHasher->hashPassword($user, $data['password']);
            $user->setPassword($hashedPassword);
        }

        $user->setUpdatedAt(new \DateTimeImmutable());
        $em->flush();

        return $this->json([
            'message' => 'Utilisateur mis à jour avec succès',
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'city' => $user->getCity(),
            'createdAt' => $user->getCreatedAt()?->format('Y-m-d H:i:s'),
            'updatedAt' => $user->getUpdatedAt()?->format('Y-m-d H:i:s'),
        ], 200);
    }

    /**
     * Supprime un utilisateur existant.
     *
     * @param int                    $id            Identifiant de l'utilisateur à supprimer.
     * @param EntityManagerInterface $em            Gestionnaire d'entités Doctrine.
     * @param UserRepository         $userRepository Permet de vérifier l'existence de l'utilisateur.
     *
     * @return JsonResponse Message de confirmation ou erreur si l'utilisateur n'existe pas.
     */
    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    public function delete(int $id, EntityManagerInterface $em, UserRepository $userRepository): JsonResponse
    {
        $user = $userRepository->find($id);
        if (!$user) {
            return $this->json(['error' => 'Utilisateur non trouvé'], 404);
        }

        $em->remove($user);
        $em->flush();

        return $this->json(['message' => 'Utilisateur supprimé avec succès'], 200);
    }
}
