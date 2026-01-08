<?php

namespace App\Controller;

use App\Repository\GuildStockRepository;
use App\Repository\ItemRecipeRepository;
use App\Repository\AvatarRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[IsGranted('ROLE_USER')]
class GuildStockController extends AbstractController
{
    #[Route('/guild-stock', name: 'guild_stock_index')]
    public function index(
        GuildStockRepository $stockRepository, 
        ItemRecipeRepository $recipeRepository,
        AvatarRepository $avatarRepository,
        HttpClientInterface $httpClient
    ): Response
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

        // Récupérer tous les avatars avec leurs skills
        $allAvatars = $avatarRepository->findAllWithUser();
        
        // Vérifier quels items peuvent être craftés par les avatars
        $craftableByAvatars = [];
        
        foreach ($recipes as $recipe) {
            $outputExternalId = $recipe->getOutput()->getExternalId();
            
            try {
                // Récupérer la liste des recettes depuis l'API
                $recipesListUrl = 'https://data-cdn.gaming.tools/paxdei/data/fr/recipe.json?version=1767546126039';
                $response = $httpClient->request('GET', $recipesListUrl);
                $allRecipes = $response->toArray();
                
                // Trouver la recette correspondante
                foreach ($allRecipes as $apiRecipe) {
                    if (isset($apiRecipe['outputs']) && is_array($apiRecipe['outputs'])) {
                        foreach ($apiRecipe['outputs'] as $output) {
                            if (isset($output['entity']['id']) && $output['entity']['id'] === $outputExternalId) {
                                // Récupérer les détails de la recette
                                $recipeDetailUrl = sprintf(
                                    'https://data-cdn.gaming.tools/paxdei/data/fr/recipe/%s.json?version=1767546126039',
                                    $apiRecipe['id']
                                );
                                
                                $detailResponse = $httpClient->request('GET', $recipeDetailUrl);
                                $recipeDetail = $detailResponse->toArray();
                                
                                // Récupérer le skill requis
                                $skillRequiredData = $recipeDetail['skillRequired'] ?? null;
                                $skillRequired = is_array($skillRequiredData) 
                                    ? ($skillRequiredData['id'] ?? null) 
                                    : $skillRequiredData;
                                
                                // Vérifier quels avatars peuvent le crafter
                                $capableAvatars = [];
                                
                                if ($skillRequired && isset($recipeDetail['craftingStats'])) {
                                    foreach ($allAvatars as $avatar) {
                                        foreach ($avatar->getAvatarSkills() as $avatarSkill) {
                                            if ($avatarSkill->getSkill()->getExternalId() === $skillRequired) {
                                                $currentLevel = $avatarSkill->getLevel();
                                                
                                                // Trouver la probabilité pour ce niveau
                                                foreach ($recipeDetail['craftingStats'] as $stat) {
                                                    if (isset($stat['level']) && $stat['level'] == $currentLevel) {
                                                        $currentProbability = $stat['calculatedProbability'] ?? 0;
                                                        
                                                        if ($currentProbability > 0.8) {
                                                            $capableAvatars[] = [
                                                                'avatar' => $avatar,
                                                                'probability' => $currentProbability,
                                                            ];
                                                        }
                                                        break;
                                                    }
                                                }
                                                break;
                                            }
                                        }
                                    }
                                }
                                
                                $craftableByAvatars[$recipe->getIngredient()->getId()] = $capableAvatars;
                                break 2; // Sortir des deux boucles
                            }
                        }
                    }
                }
            } catch (\Exception $e) {
                // En cas d'erreur, continuer sans les infos de craft
            }
        }

        return $this->render('guild_stock/index.html.twig', [
            'stocks' => $stocks,
            'recipes' => $recipesByItem,
            'craftableByAvatars' => $craftableByAvatars,
        ]);
    }
}
