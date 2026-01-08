<?php

namespace App\Entity;

use App\Repository\SpellItemRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SpellItemRepository::class)]
#[ORM\Table(name: 'spell_item')]
class SpellItem
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Spell::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Spell $spell = null;

    #[ORM\ManyToOne(targetEntity: ItemEntity::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?ItemEntity $item = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSpell(): ?Spell
    {
        return $this->spell;
    }

    public function setSpell(?Spell $spell): static
    {
        $this->spell = $spell;

        return $this;
    }

    public function getItem(): ?ItemEntity
    {
        return $this->item;
    }

    public function setItem(?ItemEntity $item): static
    {
        $this->item = $item;

        return $this;
    }
}
