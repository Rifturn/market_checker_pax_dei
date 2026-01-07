<?php

namespace App\Command;

use App\Entity\ItemRecipe;
use App\Repository\ItemEntityRepository;
use App\Repository\ItemRecipeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[AsCommand(
    name: 'app:analyze-relic-recipes',
    description: 'Analyse les recettes utilisant les reliques comme ingrédients',
)]
class AnalyzeRelicRecipesCommand extends Command
{
    public function __construct(
        private ItemEntityRepository $itemRepository,
        private ItemRecipeRepository $itemRecipeRepository,
        private EntityManagerInterface $entityManager,
        private HttpClientInterface $httpClient,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Analyse des recettes via l\'API JSON');

        // Récupérer toutes les reliques
        $items = $this->itemRepository->createQueryBuilder('i')
            ->join('i.category', 'c')
            ->where('c.name = :reliques')
            ->setParameter('reliques', 'Reliques')
            ->getQuery()
            ->getResult();

        $io->info(sprintf('Nombre de reliques trouvées : %d', count($items)));

        // Vider la table avant de la repeupler
        $io->text('Suppression des anciennes recettes...');
        $this->entityManager->createQuery('DELETE FROM App\Entity\ItemRecipe')->execute();
        $io->success('Anciennes recettes supprimées');

        $foundCount = 0;
        $notFoundCount = 0;
        $savedCount = 0;

        foreach ($items as $item) {
            $itemName = $item->getName()['Fr'] ?? $item->getName()['En'] ?? $item->getExternalId();
            $externalId = $item->getExternalId();

            $io->section(sprintf('Analyse de : %s', $itemName));

            // Construire l'URL de l'API JSON
            $apiUrl = sprintf('https://data-cdn.gaming.tools/paxdei/data/fr/item/%s.json', $externalId);

            try {
                // Faire une requête HTTP pour récupérer le JSON
                $response = $this->httpClient->request('GET', $apiUrl);
                $data = $response->toArray();

                // Vérifier si unlocksRecipes existe et n'est pas vide
                if (!isset($data['unlocksRecipes']) || empty($data['unlocksRecipes'])) {
                    $io->text('  → Pas de recettes débloquées');
                    continue;
                }

                $io->text(sprintf('  → %d recette(s) débloquée(s)', count($data['unlocksRecipes'])));

                // Récupérer l'ID de la première recette (outputs[0].entity.id)
                $firstRecipe = $data['unlocksRecipes'][0];
                
                if (!isset($firstRecipe['outputs']) || empty($firstRecipe['outputs'])) {
                    $io->text('  → Pas d\'outputs dans la recette');
                    continue;
                }

                $firstOutput = $firstRecipe['outputs'][0];
                
                if (!isset($firstOutput['entity']['id'])) {
                    $io->text('  → Pas d\'ID dans l\'entity');
                    continue;
                }

                $recipeOutputId = $firstOutput['entity']['id'];
                $recipeName = $firstOutput['entity']['name'] ?? $recipeOutputId;
                $io->text(sprintf('  → Recette débloquée : %s (%s)', $recipeName, $recipeOutputId));

                // Chercher l'item dans la base
                $foundItem = $this->itemRepository->findByExternalId($recipeOutputId);

                if ($foundItem) {
                    $foundName = $foundItem->getName()['Fr'] ?? $foundItem->getName()['En'] ?? $recipeOutputId;
                    $io->success(sprintf('FOUND : %s', $foundName));
                    $foundCount++;

                    // Créer et persister la relation
                    $recipe = new ItemRecipe();
                    $recipe->setIngredient($item);
                    $recipe->setOutput($foundItem);
                    
                    // Récupérer la quantité si disponible
                    if (isset($firstOutput['quantity'])) {
                        $recipe->setOutputQuantity((int) $firstOutput['quantity']);
                    }
                    
                    $this->entityManager->persist($recipe);
                    $savedCount++;
                } else {
                    $io->error(sprintf('NOT FOUND : %s', $recipeOutputId));
                    $notFoundCount++;
                }

            } catch (\Exception $e) {
                $io->text(sprintf('  → Erreur API : %s', $e->getMessage()));
            }

            $io->newLine();
        }

        // Flush toutes les recettes
        $io->text('Sauvegarde des recettes en base de données...');
        $this->entityManager->flush();

        $io->success(sprintf('Analyse terminée ! FOUND: %d | NOT FOUND: %d | SAVED: %d', $foundCount, $notFoundCount, $savedCount));

        return Command::SUCCESS;
    }
}
