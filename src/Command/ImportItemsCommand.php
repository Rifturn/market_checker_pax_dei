<?php

namespace App\Command;

use App\Entity\Category;
use App\Entity\ItemEntity;
use App\Repository\CategoryRepository;
use App\Repository\ItemEntityRepository;
use App\Service\PaxDeiClient;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:import-items',
    description: 'Import items from Pax Dei API into database',
)]
class ImportItemsCommand extends Command
{
    public function __construct(
        private PaxDeiClient $paxDeiClient,
        private EntityManagerInterface $entityManager,
        private CategoryRepository $categoryRepository,
        private ItemEntityRepository $itemRepository
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'Force update existing items')
            ->setHelp('This command fetches all items from Pax Dei API and stores them in the database.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $force = $input->getOption('force');

        $io->title('Import des items depuis l\'API Pax Dei');

        try {
            // Fetch items from API
            $io->section('Récupération des items depuis l\'API...');
            $apiItems = $this->paxDeiClient->fetchAllItems();
            $io->info(sprintf('✓ %d items récupérés', count($apiItems)));

            // Process items
            $io->section('Import des items dans la base de données...');
            $io->progressStart(count($apiItems));

            $stats = [
                'created' => 0,
                'updated' => 0,
                'skipped' => 0,
                'categories_created' => 0,
            ];

            $processedCategories = [];

            foreach ($apiItems as $apiItem) {
                // Find or create category
                $categoryName = $apiItem->getCategory();
                
                if (!isset($processedCategories[$categoryName])) {
                    $category = $this->categoryRepository->findOrCreateByName($categoryName);
                    $processedCategories[$categoryName] = $category;
                    
                    if ($category->getId() && !$this->entityManager->contains($category)) {
                        $stats['categories_created']++;
                    }
                }
                
                $category = $processedCategories[$categoryName];

                // Find or create item
                $item = $this->itemRepository->findByExternalId($apiItem->getId());

                if (!$item) {
                    // Create new item
                    $item = new ItemEntity();
                    $item->setExternalId($apiItem->getId());
                    $stats['created']++;
                } elseif ($force) {
                    // Update existing item
                    $stats['updated']++;
                } else {
                    // Skip existing item
                    $stats['skipped']++;
                    $io->progressAdvance();
                    continue;
                }

                // Update item properties
                $item->setName($apiItem->getName());
                $item->setIconPath($apiItem->getIconPath());
                $item->setUrl($apiItem->getUrl());
                $item->setCategory($category);
                $item->setUpdatedAt(new \DateTime());

                $this->entityManager->persist($item);

                // Flush every 100 items to optimize performance
                if (($stats['created'] + $stats['updated']) % 100 === 0) {
                    $this->entityManager->flush();
                }

                $io->progressAdvance();
            }

            // Final flush
            $this->entityManager->flush();
            $io->progressFinish();

            // Display statistics
            $io->success('Import terminé avec succès !');
            $io->table(
                ['Statistique', 'Valeur'],
                [
                    ['Items créés', $stats['created']],
                    ['Items mis à jour', $stats['updated']],
                    ['Items ignorés', $stats['skipped']],
                    ['Catégories créées', $stats['categories_created']],
                    ['Total items traités', count($apiItems)],
                ]
            );

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error('Erreur lors de l\'import : ' . $e->getMessage());
            $io->note($e->getTraceAsString());
            
            return Command::FAILURE;
        }
    }
}
