<?php

namespace App\Entity;

use App\Repository\ItemRecipeRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ItemRecipeRepository::class)]
#[ORM\Index(columns: ['ingredient_id'], name: 'idx_ingredient')]
class ItemRecipe
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: ItemEntity::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?ItemEntity $ingredient = null;

    #[ORM\ManyToOne(targetEntity: ItemEntity::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?ItemEntity $output = null;

    #[ORM\Column(nullable: true)]
    private ?int $outputQuantity = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getIngredient(): ?ItemEntity
    {
        return $this->ingredient;
    }

    public function setIngredient(?ItemEntity $ingredient): static
    {
        $this->ingredient = $ingredient;

        return $this;
    }

    public function getOutput(): ?ItemEntity
    {
        return $this->output;
    }

    public function setOutput(?ItemEntity $output): static
    {
        $this->output = $output;

        return $this;
    }

    public function getOutputQuantity(): ?int
    {
        return $this->outputQuantity;
    }

    public function setOutputQuantity(?int $outputQuantity): static
    {
        $this->outputQuantity = $outputQuantity;

        return $this;
    }
}
