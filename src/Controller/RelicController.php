<?php

namespace App\Controller;

use App\Repository\ItemRecipeRepository;
use App\Repository\ItemEntityRepository;
use App\Repository\CategoryRepository;
use App\Repository\AvatarRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class RelicController extends AbstractController
{
    #[Route('/relics/mapping', name: 'relic_mapping')]
    public function mapping(
        Request $request,
        ItemRecipeRepository $recipeRepository, 
        ItemEntityRepository $itemRepository,
        CategoryRepository $categoryRepository,
        AvatarRepository $avatarRepository
    ): Response
    {
        // Récupérer le paramètre de prière
        $prayerActive = $request->query->get('prayer', '0') === '1';
        
        // Récupérer la catégorie des reliques
        $relicCategory = $categoryRepository->findOneBy(['name' => 'Reliques']);
        
        // Récupérer tous les avatars avec leurs skills
        $allAvatars = $avatarRepository->findAllWithUser();
        
        // Récupérer les équipements craftables (wearable et wieldable en uncommon et rare)
        $craftableItems = $itemRepository->createQueryBuilder('i')
            ->where('i.type IN (:types)')
            ->andWhere('i.quality IN (:qualities)')
            ->setParameter('types', ['wearable', 'wieldable'])
            ->setParameter('qualities', ['uncommon', 'rare'])
            ->orderBy('i.quality', 'DESC')
            ->addOrderBy('i.externalId', 'ASC')
            ->getQuery()
            ->getResult();
        
        // Calculer les avatars capables pour tous les équipements craftables
        $craftableAvatars = [];
        foreach ($craftableItems as $item) {
            $capableAvatars = $this->getCapableAvatarsFromDatabase($item, $allAvatars, $prayerActive);
            if (!empty($capableAvatars)) {
                $craftableAvatars[$item->getId()] = $capableAvatars;
            }
        }
        
        // Récupérer tous les mappings relique -> équipement
        $recipes = [];
        
        if ($relicCategory) {
            // Récupérer toutes les recettes où l'ingrédient est une relique
            $allRecipes = $recipeRepository->createQueryBuilder('r')
                ->join('r.ingredient', 'i')
                ->join('r.output', 'o')
                ->leftJoin('i.category', 'c')
                ->where('c.id = :categoryId')
                ->setParameter('categoryId', $relicCategory->getId())
                ->getQuery()
                ->getResult();
            
            // Organiser les données pour l'affichage
            foreach ($allRecipes as $recipe) {
                $ingredient = $recipe->getIngredient();
                $output = $recipe->getOutput();
                
                // Calculer les avatars capables de crafter l'équipement depuis les données en BDD
                $capableAvatars = $this->getCapableAvatarsFromDatabase($output, $allAvatars, $prayerActive);
                
                $recipes[] = [
                    'relic' => $ingredient,
                    'relicName' => $ingredient->getName()['Fr'] ?? $ingredient->getName()['En'] ?? $ingredient->getExternalId(),
                    'equipment' => $output,
                    'equipmentName' => $output->getName()['Fr'] ?? $output->getName()['En'] ?? $output->getExternalId(),
                    'quantity' => $recipe->getOutputQuantity() ?? 1,
                    'capableAvatars' => $capableAvatars,
                ];
            }
            
            // Trier par nom de relique
            usort($recipes, function($a, $b) {
                return strcasecmp($a['relicName'], $b['relicName']);
            });
        }
        
        return $this->render('relic/mapping.html.twig', [
            'recipes' => $recipes,
            'totalRecipes' => count($recipes),
            'craftableItems' => $craftableItems,
            'craftableAvatars' => $craftableAvatars,
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

