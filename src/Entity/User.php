<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use App\Entity\Role;

/**
 * Entité représentant un utilisateur de l'application.
 *
 * Elle implémente les interfaces nécessaires à Symfony Security
 * pour l'authentification et la gestion des rôles.
 */
#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    /**
     * Identifiant technique de l'utilisateur.
     */
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /**
     * Adresse email, utilisée comme identifiant de connexion.
     */
    #[ORM\Column(length: 180, unique: true)]
    private ?string $email = null;

    /**
     * Mot de passe hashé (jamais stocker le mot de passe en clair).
     */
    #[ORM\Column]
    private ?string $password = null;

    /**
     * Rôles associés à l'utilisateur (relation ManyToMany avec Role).
     *
     * On stocke ici les entités Role, et getRoles() renvoie les noms.
     */
    #[ORM\ManyToMany(targetEntity: Role::class, inversedBy: 'users')]
    #[ORM\JoinTable(name: 'user_role')]
    private Collection $roles;

    /**
     * Ville de résidence de l'utilisateur.
     */
    #[ORM\Column(length: 255)]
    private ?string $city = null;

    /**
     * Date de création du compte utilisateur.
     */
    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    /**
     * Date de dernière mise à jour du compte (nullable).
     */
    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    /**
     * Initialise la collection de rôles.
     */
    public function __construct()
    {
        $this->roles = new ArrayCollection();
    }

    /* ===================== */
    /* Getters / Setters     */
    /* ===================== */

    /**
     * Retourne l'identifiant de l'utilisateur.
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Retourne l'email de l'utilisateur.
     */
    public function getEmail(): ?string
    {
        return $this->email;
    }

    /**
     * Définit l'email de l'utilisateur.
     */
    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    /**
     * Identifiant unique pour Symfony Security (Symfony 6+).
     * Ici, on utilise l'email comme identifiant.
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    /**
     * Retourne le mot de passe hashé.
     */
    public function getPassword(): string
    {
        return (string) $this->password;
    }

    /**
     * Définit le mot de passe hashé.
     */
    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    /**
     * Retourne la liste des noms de rôles (ROLE_USER, ROLE_ADMIN, ...).
     *
     * Symfony utilise ce tableau de chaînes pour les vérifications de sécurité.
     */
    public function getRoles(): array
    {
        $roles = [];

        foreach ($this->roles as $role) {
            $roles[] = $role->getName();
        }

        // Rôle minimum : on s'assure que tout utilisateur a au moins ROLE_USER
        if (!in_array('ROLE_USER', $roles)) {
            $roles[] = 'ROLE_USER';
        }

        return $roles;
    }

    /**
     * Retourne la collection d'entités Role (côté objet, pas les noms).
     */
    public function getRoleEntities(): Collection
    {
        return $this->roles;
    }

    /**
     * Ajoute un rôle à l'utilisateur.
     */
    public function addRole(Role $role): static
    {
        if (!$this->roles->contains($role)) {
            $this->roles->add($role);
        }

        return $this;
    }

    /**
     * Retire un rôle de l'utilisateur.
     */
    public function removeRole(Role $role): static
    {
        $this->roles->removeElement($role);

        return $this;
    }

    /**
     * @see UserInterface
     * Méthode prévue pour effacer des données sensibles en mémoire.
     *
     * Ici nous n'avons rien de plus à faire.
     */
    public function eraseCredentials(): void {}

    /**
     * Retourne la ville de l'utilisateur.
     */
    public function getCity(): ?string
    {
        return $this->city;
    }

    /**
     * Définit la ville de l'utilisateur.
     */
    public function setCity(string $city): static
    {
        $this->city = $city;

        return $this;
    }

    /**
     * Retourne la date de création du compte.
     */
    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    /**
     * Définit la date de création du compte.
     */
    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * Retourne la date de dernière mise à jour du compte.
     */
    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    /**
     * Définit la date de dernière mise à jour du compte.
     */
    public function setUpdatedAt(?\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }
}
