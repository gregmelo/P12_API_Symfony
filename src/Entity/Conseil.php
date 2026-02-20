<?php

namespace App\Entity;

use App\Repository\ConseilRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Entité représentant un conseil de jardinage.
 *
 * Un conseil peut être associé à plusieurs mois (relation ManyToMany
 * avec l'entité Mois) afin d'être réutilisé sur différentes périodes.
 */
#[ORM\Entity(repositoryClass: ConseilRepository::class)]
class Conseil
{
    /**
     * Identifiant technique du conseil.
     */
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /**
     * Contenu textuel du conseil (peut être relativement long).
     */
    #[ORM\Column(type: Types::TEXT)]
    private ?string $content = null;

    /**
     * Date de création du conseil.
     */
    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    /**
     * Date de dernière mise à jour du conseil.
     */
    #[ORM\Column]
    private ?\DateTimeImmutable $updatedAt = null;

    /**
     * Mois auxquels ce conseil est associé.
     *
     * @var Collection<int, Mois>
     */
    #[ORM\ManyToMany(targetEntity: Mois::class, inversedBy: 'conseils')]
    private Collection $mois;

    /**
     * Initialise la collection de mois liés.
     */
    public function __construct()
    {
        $this->mois = new ArrayCollection();
    }

    /**
     * Retourne l'identifiant du conseil.
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Retourne le contenu du conseil.
     */
    public function getContent(): ?string
    {
        return $this->content;
    }

    /**
     * Définit le contenu du conseil.
     */
    public function setContent(string $content): static
    {
        $this->content = $content;

        return $this;
    }

    /**
     * Retourne la date de création.
     */
    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    /**
     * Définit la date de création.
     */
    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * Retourne la date de dernière mise à jour.
     */
    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    /**
     * Définit la date de dernière mise à jour.
     */
    public function setUpdatedAt(\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    /**
     * Retourne la collection de mois associés à ce conseil.
     *
     * @return Collection<int, Mois>
     */
    public function getMois(): Collection
    {
        return $this->mois;
    }

    /**
     * Ajoute une association entre ce conseil et un mois donné.
     */
    public function addMois(Mois $mois): static
    {
        if (!$this->mois->contains($mois)) {
            $this->mois->add($mois);
        }

        return $this;
    }

    /**
     * Supprime l'association entre ce conseil et un mois.
     */
    public function removeMois(Mois $mois): static
    {
        $this->mois->removeElement($mois);

        return $this;
    }
}
