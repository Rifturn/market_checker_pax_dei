<?php

namespace App\Command;

use App\Repository\ItemEntityRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[AsCommand(
    name: 'app:update-item-recipes',
    description: 'Fetch and store recipe data (skill required, crafting stats) for all items',
)]
class UpdateItemRecipesCommand extends Command
{
    public function __construct(
        private ItemEntityRepository $itemRepository,
        private EntityManagerInterface $entityManager,
        private HttpClientInterface $httpClient
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Mise à jour des données de recettes pour les items');

        try {
            // Récupérer la liste complète des recettes depuis l'API
            $io->section('Récupération de la liste des recettes...');
            $recipesListUrl = 'https://data-cdn.gaming.tools/paxdei/data/fr/recipe.json?version=1767546126039';
            $response = $this->httpClient->request('GET', $recipesListUrl);
            $allRecipes = $response->toArray();
            
            $io->success(sprintf('✓ %d recettes trouvées', count($allRecipes)));

            // Indexer les recettes par output item ID
            $io->section('Indexation des recettes par item...');
            $recipesByItemId = [];
            
            foreach ($allRecipes as $recipe) {
                if (isset($recipe['outputs']) && is_array($recipe['outputs'])) {
                    foreach ($recipe['outputs'] as $output) {
                        if (isset($output['entity']['id'])) {
                            $itemId = $output['entity']['id'];
                            if (!isset($recipesByItemId[$itemId])) {
                                $recipesByItemId[$itemId] = [];
                            }
                            $recipesByItemId[$itemId][] = $recipe['id'];
                        }
                    }
                }
            }
            
            $io->success(sprintf('✓ Recettes indexées pour %d items différents', count($recipesByItemId)));

            // Récupérer les détails de chaque recette et les stocker
            $io->section('Récupération des détails des recettes...');
            $processedRecipes = 0;
            $updatedItems = 0;
            
            $io->progressStart(count($recipesByItemId));
            
            foreach ($recipesByItemId as $itemExternalId => $recipeIds) {
                // Trouver l'item dans la base de données
                $item = $this->itemRepository->findOneBy(['externalId' => $itemExternalId]);
                
                if (!$item) {
                    $io->progressAdvance();
                    continue;
                }
                
                // Récupérer les détails de la première recette (on suppose qu'il n'y a qu'une recette principale par item)
                $recipeId = $recipeIds[0];
                $recipeDetailUrl = sprintf(
                    'https://data-cdn.gaming.tools/paxdei/data/fr/recipe/%s.json?version=1767546126039',
                    $recipeId
                );
                
                try {
                    $detailResponse = $this->httpClient->request('GET', $recipeDetailUrl);
                    $recipeDetail = $detailResponse->toArray();
                    
                    // Extraire les informations importantes
                    $skillRequiredData = $recipeDetail['skillRequired'] ?? null;
                    $skillRequired = is_array($skillRequiredData) 
                        ? ($skillRequiredData['id'] ?? null) 
                        : $skillRequiredData;
                    
                    // Stocker les données de recette
                    $recipeData = [
                        'recipeId' => $recipeId,
                        'skillRequired' => $skillRequired,
                        'craftingStats' => $recipeDetail['craftingStats'] ?? [],
                        'inputs' => $recipeDetail['inputs'] ?? [],
                    ];
                    
                    $item->setRecipeData($recipeData);
                    $item->setRecipeUrl($recipeDetailUrl);
                    $item->setUpdatedAt(new \DateTime());
                    
                    $updatedItems++;
                    $processedRecipes++;
                    
                    // Flush tous les 50 items pour éviter les problèmes de mémoire
                    if ($processedRecipes % 50 === 0) {
                        $this->entityManager->flush();
                    }
                } catch (\Exception $e) {
                    // Ignorer les erreurs de récupération de détails
                }
                
                $io->progressAdvance();
            }
            
            // Flush final
            $this->entityManager->flush();
            $io->progressFinish();

            $io->success([
                sprintf('✓ %d items mis à jour avec leurs données de recette', $updatedItems),
                sprintf('✓ %d recettes traitées', $processedRecipes),
            ]);

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error('Erreur lors de la mise à jour des recettes : ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
