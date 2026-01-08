<?php

namespace App\Command;

use App\Entity\Spell;
use App\Entity\SpellItem;
use App\Repository\SpellRepository;
use App\Repository\ItemEntityRepository;
use App\Repository\SpellItemRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[AsCommand(
    name: 'app:import-spells',
    description: 'Importe tous les sorts depuis l\'API Pax Dei',
)]
class ImportSpellsCommand extends Command
{
    public function __construct(
        private SpellRepository $spellRepository,
        private ItemEntityRepository $itemRepository,
        private SpellItemRepository $spellItemRepository,
        private EntityManagerInterface $entityManager,
        private HttpClientInterface $httpClient,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Import des sorts depuis l\'API Pax Dei');

        // URL de l'API
        $apiUrl = 'https://data-cdn.gaming.tools/paxdei/data/fr/spell.json?version=1767546126039';

        try {
            $io->text('Récupération des données depuis l\'API...');
            $response = $this->httpClient->request('GET', $apiUrl);
            $spellsData = $response->toArray();

            $io->info(sprintf('Nombre de sorts récupérés : %d', count($spellsData)));

            $createdCount = 0;
            $updatedCount = 0;
            $errorCount = 0;
            $sourcesLinkedCount = 0;

            $io->text('Traitement des sorts...');
            $io->progressStart(count($spellsData));

            foreach ($spellsData as $spellData) {
                try {
                    // Vérifier si le sort existe déjà
                    $spell = $this->spellRepository->findByExternalId($spellData['id']);

                    if (!$spell) {
                        $spell = new Spell();
                        $spell->setExternalId($spellData['id']);
                        $createdCount++;
                    } else {
                        $updatedCount++;
                    }

                    // Remplir les données de base
                    $spell->setName($spellData['name']);
                    $spell->setIconPath($spellData['iconPath'] ?? null);
                    $spell->setCooldownDuration($spellData['cooldownDuration'] ?? null);
                    $spell->setRange($spellData['range'] ?? null);
                    $spell->setCostAttribute($spellData['costAttribute'] ?? null);
                    $spell->setCostAmountMin($spellData['costAmountMin'] ?? null);
                    $spell->setEntityType($spellData['entityType']);
                    $spell->setEntityTypeName($spellData['entityTypeName']);
                    $spell->setListingPath($spellData['listingPath']);
                    $spell->setCategoryIds($spellData['categoryIds'] ?? []);

                    $this->entityManager->persist($spell);
                    
                    // Flush pour obtenir l'ID du spell
                    $this->entityManager->flush();

                    // Récupérer les détails du spell (description + sources)
                    try {
                        $detailUrl = sprintf('https://data-cdn.gaming.tools/paxdei/data/fr/spell/%s.json?version=1767546126039', $spellData['id']);
                        $detailResponse = $this->httpClient->request('GET', $detailUrl);
                        $detailData = $detailResponse->toArray();

                        // Mettre à jour la description
                        if (isset($detailData['description'])) {
                            $spell->setDescription($detailData['description']);
                        }

                        // Supprimer les anciennes liaisons spell-item pour ce spell
                        $oldLinks = $this->spellItemRepository->findBySpell($spell->getId());
                        foreach ($oldLinks as $link) {
                            $this->entityManager->remove($link);
                        }
                        $this->entityManager->flush();

                        // Créer les nouvelles liaisons avec les items sources
                        if (isset($detailData['sources']) && is_array($detailData['sources'])) {
                            foreach ($detailData['sources'] as $source) {
                                if (isset($source['id'])) {
                                    $item = $this->itemRepository->findByExternalId($source['id']);
                                    if ($item) {
                                        $spellItem = new SpellItem();
                                        $spellItem->setSpell($spell);
                                        $spellItem->setItem($item);
                                        $this->entityManager->persist($spellItem);
                                        $sourcesLinkedCount++;
                                    }
                                }
                            }
                        }

                        $this->entityManager->flush();
                    } catch (\Exception $e) {
                        // Erreur lors de la récupération des détails, on continue
                    }

                    $io->progressAdvance();

                } catch (\Exception $e) {
                    $errorCount++;
                    $io->error(sprintf('Erreur pour le sort %s : %s', $spellData['id'] ?? 'unknown', $e->getMessage()));
                }
            }

            // Flush final
            $this->entityManager->flush();
            $io->progressFinish();

            $io->newLine();
            $io->success('Import terminé !');
            $io->table(
                ['Statut', 'Nombre'],
                [
                    ['Créés', $createdCount],
                    ['Mis à jour', $updatedCount],
                    ['Sources liées', $sourcesLinkedCount],
                    ['Erreurs', $errorCount],
                    ['Total', $createdCount + $updatedCount],
                ]
            );

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $io->error(sprintf('Erreur lors de la récupération des données : %s', $e->getMessage()));
            return Command::FAILURE;
        }
    }
}
