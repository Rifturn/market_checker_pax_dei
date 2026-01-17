<?php

namespace App\Controller;

use App\Repository\GuildStockRepository;
use App\Repository\ItemRecipeRepository;
use App\Repository\AvatarRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
class GuildStockController extends AbstractController
{
    #[Route('/guild-stock', name: 'guild_stock_index')]
    public function index(
        Request $request,
        GuildStockRepository $stockRepository, 
        ItemRecipeRepository $recipeRepository,
        AvatarRepository $avatarRepository
    ): Response
    {
        // Récupérer le paramètre de prière
        $prayerActive = $request->query->get('prayer', '0') === '1';
        
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
            $output = $recipe->getOutput();
            $capableAvatars = $this->getCapableAvatarsFromDatabase($output, $allAvatars, $prayerActive);
            
            if (!empty($capableAvatars)) {
                $craftableByAvatars[$recipe->getIngredient()->getId()] = $capableAvatars;
            }
        }

        return $this->render('guild_stock/index.html.twig', [
            'stocks' => $stocks,
            'recipes' => $recipesByItem,
            'craftableByAvatars' => $craftableByAvatars,
            'prayerActive' => $prayerActive,
        ]);
    }
    
    private function getCapableAvatarsFromDatabase($item, $allAvatars, bool $prayerActive = false): array
    {
        $capableAvatars = [];
        
        // Récupérer les données de recette depuis la base de données
        $recipeData = $item->getRecipeData();
        
        if (!$recipeData || !isset($recipeData['skillRequired']) || !isset($recipeData['craftingStats'])) {
            return [];
        }
        
        $skillRequired = $recipeData['skillRequired'];
        $craftingStats = $recipeData['craftingStats'];
        
        // Calculer les avatars capables de crafter (>80% de réussite)
        foreach ($allAvatars as $avatar) {
            // Trouver le skill correspondant dans l'avatar
            foreach ($avatar->getAvatarSkills() as $avatarSkill) {
                if ($avatarSkill->getSkill()->getExternalId() === $skillRequired) {
                    $currentLevel = $avatarSkill->getLevel();
                    
                    // Si la prière est active, ajouter +1 au niveau
                    $effectiveLevel = $prayerActive ? $currentLevel + 1 : $currentLevel;
                    
                    // Trouver la probabilité pour ce niveau effectif
                    foreach ($craftingStats as $stat) {
                        if (isset($stat['level']) && $stat['level'] == $effectiveLevel) {
                            $currentProbability = $stat['calculatedProbability'] ?? 0;
                            
                            // Si la probabilité actuelle est > 80%, ajouter l'avatar
                            if ($currentProbability > 0.8) {
                                $capableAvatars[] = [
                                    'avatar' => $avatar,
                                    'level' => $currentLevel,
                                    'effectiveLevel' => $effectiveLevel,
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
        
        return $capableAvatars;
    }
}
