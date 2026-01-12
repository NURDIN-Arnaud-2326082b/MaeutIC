<?php

/**
 * Entité Tag - Représente un tag/mot-clé
 *
 * Cette entité gère les tags utilisés pour catégoriser :
 * - Les auteurs de la bibliothèque
 * - Les articles et livres
 * - Les profils utilisateurs
 *
 * Les tags permettent de filtrer et rechercher du contenu par thématiques
 */

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class Tag
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }
}
