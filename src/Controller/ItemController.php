<?php

namespace App\Controller;

use App\Entity\ItemEntity;
use App\Repository\ItemEntityRepository;
use App\Repository\CategoryRepository;
use App\Repository\SpellItemRepository;
use App\Repository\AvatarRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ItemController extends AbstractController
{
    #[Route('/items', name: 'item_index')]
    public function index(Request $request, ItemEntityRepository $itemRepository, CategoryRepository $categoryRepository): Response
    {
        // Récupérer toutes les catégories
        $categories = $categoryRepository->findAll();
        
        // Récupérer le filtre de catégorie depuis la requête
        $categoryFilter = $request->query->get('category');
        
        // Récupérer les items
        if ($categoryFilter) {
            $items = $itemRepository->findBy(['category' => $categoryFilter], ['quality' => 'ASC']);
        } else {
            $items = $itemRepository->findAllWithCategory();
        }
        
        // Trier par qualité puis par nom
        usort($items, function($a, $b) {
            $qualityOrder = ['rare' => 1, 'uncommon' => 2, 'common' => 3];
            $orderA = $qualityOrder[$a->getQuality()] ?? 99;
            $orderB = $qualityOrder[$b->getQuality()] ?? 99;
            
            if ($orderA === $orderB) {
                return ($a->getName()['Fr'] ?? $a->getExternalId()) <=> ($b->getName()['Fr'] ?? $b->getExternalId());
            }
            
            return $orderA - $orderB;
        });
        
        return $this->render('item/index.html.twig', [
            'items' => $items,
            'categories' => $categories,
            'currentCategory' => $categoryFilter,
        ]);
    }

    #[Route('/item/{id}', name: 'item_show', methods: ['GET'])]
    public function show(ItemEntity $item, SpellItemRepository $spellItemRepository, AvatarRepository $avatarRepository, \Symfony\Contracts\HttpClient\HttpClientInterface $httpClient): Response
    {
        // Récupérer les spells liés à cet item
        $spellItems = $spellItemRepository->findByItem($item->getId());
        
        // Récupérer tous les avatars avec leurs skills
        $allAvatars = $avatarRepository->findAllWithUser();
        
        // Récupérer les recettes depuis l'API
        $recipes = [];
        try {
            // 1. Récupérer la liste complète des recettes
            $recipesListUrl = 'https://data-cdn.gaming.tools/paxdei/data/fr/recipe.json?version=1767546126039';
            $response = $httpClient->request('GET', $recipesListUrl);
            $allRecipes = $response->toArray();
            
            // 2. Filtrer les recettes dont l'output correspond à notre item
            foreach ($allRecipes as $recipe) {
                if (isset($recipe['outputs']) && is_array($recipe['outputs'])) {
                    foreach ($recipe['outputs'] as $output) {
                        if (isset($output['entity']['id']) && $output['entity']['id'] === $item->getExternalId()) {
                            // 3. Récupérer les détails de la recette
                            $recipeDetailUrl = sprintf(
                                'https://data-cdn.gaming.tools/paxdei/data/fr/recipe/%s.json?version=1767546126039',
                                $recipe['id']
                            );
                            
                            try {
                                $detailResponse = $httpClient->request('GET', $recipeDetailUrl);
                                $recipeDetail = $detailResponse->toArray();
                                
                                // Récupérer le skill requis (peut être un array ou un string)
                                $skillRequiredData = $recipeDetail['skillRequired'] ?? null;
                                $skillRequired = is_array($skillRequiredData) 
                                    ? ($skillRequiredData['id'] ?? null) 
                                    : $skillRequiredData;
                                
                                // Calculer les avatars capables de crafter (>80% de réussite)
                                $capableAvatars = [];
                                
                                if ($skillRequired && isset($recipeDetail['craftingStats'])) {
                                    foreach ($allAvatars as $avatar) {
                                        // Trouver le skill correspondant dans l'avatar
                                        foreach ($avatar->getAvatarSkills() as $avatarSkill) {
                                            if ($avatarSkill->getSkill()->getExternalId() === $skillRequired) {
                                                $currentLevel = $avatarSkill->getLevel();
                                                
                                                // Trouver la probabilité pour ce niveau dans craftingStats
                                                $currentProbability = null;
                                                $nextProbability = null;
                                                
                                                foreach ($recipeDetail['craftingStats'] as $stat) {
                                                    if (isset($stat['level']) && $stat['level'] == $currentLevel) {
                                                        $currentProbability = $stat['calculatedProbability'] ?? 0;
                                                    }
                                                    if (isset($stat['level']) && $stat['level'] == ($currentLevel + 1)) {
                                                        $nextProbability = $stat['calculatedProbability'] ?? 0;
                                                    }
                                                }
                                                
                                                // Si la probabilité actuelle est > 80%, ajouter l'avatar
                                                if ($currentProbability !== null && $currentProbability > 0.8) {
                                                    $capableAvatars[] = [
                                                        'avatar' => $avatar,
                                                        'skill' => $avatarSkill->getSkill(),
                                                        'level' => $currentLevel,
                                                        'currentProbability' => $currentProbability,
                                                        'nextProbability' => $nextProbability,
                                                    ];
                                                }
                                                
                                                break;
                                            }
                                        }
                                    }
                                }
                                
                                // Ajouter les craftingStats à notre tableau
                                if (isset($recipeDetail['craftingStats'])) {
                                    $recipes[] = [
                                        'id' => $recipe['id'],
                                        'name' => $recipe['name'] ?? 'Recette sans nom',
                                        'craftingStats' => $recipeDetail['craftingStats'],
                                        'inputs' => $recipeDetail['inputs'] ?? [],
                                        'outputs' => $recipeDetail['outputs'] ?? [],
                                        'skillRequired' => $skillRequired,
                                        'capableAvatars' => $capableAvatars,
                                    ];
                                }
                            } catch (\Exception $e) {
                                // Ignorer les erreurs de récupération de détails
                            }
                            
                            break; // Sortir de la boucle outputs
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            // En cas d'erreur, continuer sans recettes
        }
        
        return $this->render('item/show.html.twig', [
            'item' => $item,
            'spellItems' => $spellItems,
            'recipes' => $recipes,
        ]);
    }
}
