<?php

namespace App\Entity;

use App\Repository\MoisRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * Entité représentant un mois de l'année.
 *
 * Chaque mois possède un numéro (1 à 12) et un nom, et peut être
 * associé à plusieurs conseils (relation ManyToMany avec Conseil).
 */
#[ORM\Entity(repositoryClass: MoisRepository::class)]
class Mois
{
    /**
     * Identifiant technique du mois.
     */
    #[ORM\Id]
    #[ORM\GeneratedValue]
    /**
     * Numéro du mois (1 = janvier, 12 = décembre).
     */
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?int $number = null;

    /**
     * Nom du mois (ex : "Janvier").
     */
    #[ORM\Column(length: 20)]
    private ?string $name = null;

    /**
     * Conseils associés à ce mois.
     *
     * @var Collection<int, Conseil>
     */
    #[ORM\ManyToMany(targetEntity: Conseil::class, mappedBy: 'mois')]
    private Collection $conseils;

    /**
     * Initialise la collection de conseils associés.
     */
    public function __construct()
    {
        $this->conseils = new ArrayCollection();
    }

    /**
     * Retourne l'identifiant du mois.
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Retourne le numéro du mois.
     */
    public function getNumber(): ?int
    {
        return $this->number;
    }

    /**
     * Définit le numéro du mois.
     */
    public function setNumber(int $number): static
    {
        $this->number = $number;

        return $this;
    }

    /**
     * Retourne le nom du mois.
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * Définit le nom du mois.
     */
    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Retourne la liste des conseils associés à ce mois.
     *
     * @return Collection<int, Conseil>
     */
    public function getConseils(): Collection
    {
        return $this->conseils;
    }

    /**
     * Ajoute un conseil à ce mois, et met à jour l'autre côté de la relation.
     */
    public function addConseil(Conseil $conseil): static
    {
        if (!$this->conseils->contains($conseil)) {
            $this->conseils->add($conseil);
            $conseil->addMois($this);
        }

        return $this;
    }

    /**
     * Retire un conseil de ce mois, et met à jour l'autre côté de la relation.
     */
    public function removeConseil(Conseil $conseil): static
    {
        if ($this->conseils->removeElement($conseil)) {
            $conseil->removeMois($this);
        }

        return $this;
    }
}
