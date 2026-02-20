<?php

namespace App\Entity;

use App\Repository\RoleRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * Entité représentant un rôle de sécurité (ROLE_USER, ROLE_ADMIN, ...).
 */
#[ORM\Entity(repositoryClass: RoleRepository::class)]
class Role
{
    /**
     * Identifiant technique du rôle.
     */
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /**
     * Nom du rôle sous forme de chaîne (ex : "ROLE_USER").
     */
    #[ORM\Column(length: 50)]
    private ?string $name = null;

    /**
     * Retourne l'identifiant du rôle.
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Retourne le nom du rôle.
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * Définit le nom du rôle.
     */
    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }
}
