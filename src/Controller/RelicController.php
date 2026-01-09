<?php

namespace App\Controller;

use App\Repository\ItemRecipeRepository;
use App\Repository\CategoryRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class RelicController extends AbstractController
{
    #[Route('/relics/mapping', name: 'relic_mapping')]
    public function mapping(ItemRecipeRepository $recipeRepository, CategoryRepository $categoryRepository): Response
    {
        // Récupérer la catégorie des reliques
        $relicCategory = $categoryRepository->findOneBy(['name' => 'Reliques']);
        
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
                
                $recipes[] = [
                    'relic' => $ingredient,
                    'relicName' => $ingredient->getName()['Fr'] ?? $ingredient->getName()['En'] ?? $ingredient->getExternalId(),
                    'equipment' => $output,
                    'equipmentName' => $output->getName()['Fr'] ?? $output->getName()['En'] ?? $output->getExternalId(),
                    'quantity' => $recipe->getOutputQuantity() ?? 1,
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
        ]);
    }
}
