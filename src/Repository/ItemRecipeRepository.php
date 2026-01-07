<?php

namespace App\Repository;

use App\Entity\ItemEntity;
use App\Entity\ItemRecipe;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ItemRecipe>
 */
class ItemRecipeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ItemRecipe::class);
    }

    /**
     * Trouve la recette (output) pour un ingredient donné
     */
    public function findOutputByIngredient(ItemEntity $ingredient): ?ItemRecipe
    {
        return $this->createQueryBuilder('r')
            ->where('r.ingredient = :ingredient')
            ->setParameter('ingredient', $ingredient)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Trouve toutes les recettes pour plusieurs ingredients
     * @return ItemRecipe[]
     */
    public function findByIngredients(array $ingredients): array
    {
        return $this->createQueryBuilder('r')
            ->where('r.ingredient IN (:ingredients)')
            ->setParameter('ingredients', $ingredients)
            ->getQuery()
            ->getResult();
    }
}
