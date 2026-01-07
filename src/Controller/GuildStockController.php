<?php

namespace App\Controller;

use App\Repository\GuildStockRepository;
use App\Repository\ItemRecipeRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
class GuildStockController extends AbstractController
{
    #[Route('/guild-stock', name: 'guild_stock_index')]
    public function index(GuildStockRepository $stockRepository, ItemRecipeRepository $recipeRepository): Response
    {
        $stocks = $stockRepository->findBy([], ['updatedAt' => 'DESC']);

        // Récupérer les recettes pour tous les items en stock
        $items = array_map(fn($stock) => $stock->getItem(), $stocks);
        $recipes = $recipeRepository->findByIngredients($items);
        
        // Indexer les recettes par item_id pour un accès facile
        $recipesByItem = [];
        foreach ($recipes as $recipe) {
            $recipesByItem[$recipe->getIngredient()->getId()] = $recipe;
        }

        return $this->render('guild_stock/index.html.twig', [
            'stocks' => $stocks,
            'recipes' => $recipesByItem,
        ]);
    }
}
