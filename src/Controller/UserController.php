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

#[Route('/user', name: 'user_')]
class UserController extends AbstractController
{
    #[Route('', name: 'create', methods: ['POST'])]
    public function create(
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $passwordHasher,
        UserRepository $userRepository
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);

        // Validation minimale
        if (empty($data['email']) || empty($data['password']) || empty($data['city'])) {
            return $this->json(['error' => 'Email, mot de passe et ville sont obligatoires'], 400);
        }

        // Vérifie si l'utilisateur existe déjà
        if ($userRepository->findOneBy(['email' => $data['email']])) {
        return $this->json(['error' => 'Cet email est déjà utilisé'], 409);
        }

        // Création de l'utilisateur
        $user = new User();
        $user->setEmail($data['email']);
        $user->setCity($data['city']);
        $user->setCreatedAt(new \DateTimeImmutable());

        // Hash du mot de passe
        $hashedPassword = $passwordHasher->hashPassword($user, $data['password']);
        $user->setPassword($hashedPassword);

        $em->persist($user);
        $em->flush();

        return $this->json([
            'message' => 'Utilisateur créé avec succès',
            'id' => $user->getId()
        ], 201);
    }

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

        $data = json_decode($request->getContent(), true);
        if (!is_array($data)) {
            return $this->json(['error' => 'Corps de requête invalide'], 400);
        }

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

        if (array_key_exists('city', $data)) {
            if (empty($data['city'])) {
                return $this->json(['error' => 'La ville ne peut pas être vide'], 400);
            }
            $user->setCity($data['city']);
        }

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
