<?php

namespace App\Command;

use App\Entity\NotifiedListing;
use App\Repository\ItemEntityRepository;
use App\Repository\NotifiedListingRepository;
use App\Service\PaxDeiClient;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

#[AsCommand(
    name: 'app:check-market-relics',
    description: 'Vérifie le marché pour détecter les nouvelles annonces de reliques',
)]
class CheckMarketRelicsCommand extends Command
{
    private const ALERT_FILE = 'var/market_alerts.txt';

    public function __construct(
        private PaxDeiClient $paxDeiClient,
        private ItemEntityRepository $itemRepository,
        private NotifiedListingRepository $notifiedListingRepository,
        private EntityManagerInterface $entityManager,
        private ParameterBagInterface $params,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Vérification du marché pour les reliques');

        // Récupérer toutes les reliques
        $relics = $this->itemRepository->createQueryBuilder('i')
            ->join('i.category', 'c')
            ->where('c.name = :reliques')
            ->setParameter('reliques', 'Reliques')
            ->getQuery()
            ->getResult();

        $io->info(sprintf('Nombre de reliques à surveiller : %d', count($relics)));

        // Créer un index des reliques par external_id pour un accès rapide
        $relicsByExternalId = [];
        foreach ($relics as $relic) {
            $relicsByExternalId[$relic->getExternalId()] = $relic;
        }

        $io->text('Récupération de tous les listings...');
        
        // Récupérer tous les listings de toutes les régions
        $allListings = $this->paxDeiClient->fetchAllListings();
        
        $io->info(sprintf('Total de listings récupérés : %d', count($allListings)));

        $newListings = [];
        $processedCount = 0;

        foreach ($allListings as $listing) {
            $processedCount++;
            
            // Vérifier si ce listing concerne une relique
            if (!isset($relicsByExternalId[$listing->getItemId()])) {
                continue; // Pas une relique, on ignore
            }

            $listingId = $listing->getId();
            
            // Vérifier si on a déjà notifié ce listing
            if ($this->notifiedListingRepository->isNotified($listingId)) {
                continue; // Déjà notifié
            }

            // Nouveau listing de relique !
            $relic = $relicsByExternalId[$listing->getItemId()];
            $itemName = $relic->getName()['Fr'] ?? $relic->getName()['En'] ?? $listing->getItemId();
            
            $newListings[] = [
                'listing' => $listing,
                'item' => $relic,
                'itemName' => $itemName,
                'zone' => $listing->getZone(),
            ];

            // Marquer comme notifié
            $notified = new NotifiedListing();
            $notified->setListingId($listingId);
            $notified->setItemExternalId($listing->getItemId());
            $notified->setZone($listing->getZone());
            $notified->setPrice($listing->getPrice());
            $notified->setQuantity($listing->getQuantity());
            $notified->setNotifiedAt(new \DateTimeImmutable());

            $this->entityManager->persist($notified);
        }

        $io->text(sprintf('Listings traités : %d', $processedCount));

        // Sauvegarder les notifications en base
        $this->entityManager->flush();

        // Écrire les alertes dans un fichier
        if (count($newListings) > 0) {
            $this->writeAlerts($newListings, $io);
            $io->success(sprintf('%d nouvelle(s) annonce(s) détectée(s) !', count($newListings)));
        } else {
            $io->info('Aucune nouvelle annonce détectée.');
        }

        // Nettoyer les anciennes notifications (> 7 jours)
        $deleted = $this->notifiedListingRepository->cleanOldNotifications(7);
        if ($deleted > 0) {
            $io->text(sprintf('✓ %d ancienne(s) notification(s) nettoyée(s)', $deleted));
        }

        return Command::SUCCESS;
    }

    private function writeAlerts(array $newListings, SymfonyStyle $io): void
    {
        $projectDir = $this->params->get('kernel.project_dir');
        $alertFile = $projectDir . '/' . self::ALERT_FILE;
        $timestamp = (new \DateTimeImmutable())->format('Y-m-d H:i:s');

        $content = "\n" . str_repeat('=', 80) . "\n";
        $content .= "🔔 NOUVELLES ANNONCES DE RELIQUES - {$timestamp}\n";
        $content .= str_repeat('=', 80) . "\n\n";

        foreach ($newListings as $data) {
            $listing = $data['listing'];
            $item = $data['item'];
            $itemName = $data['itemName'];
            $zone = $data['zone'];

            $itemUrl = sprintf('http://localhost:8000/item/%d', $item->getId());

            $content .= sprintf(
                "📦 %s\n   Zone: %s\n   Prix: %s gold\n   Quantité: %d\n   Lien: %s\n\n",
                $itemName,
                $zone,
                number_format($listing->getPrice()),
                $listing->getQuantity(),
                $itemUrl
            );
        }

        $content .= str_repeat('-', 80) . "\n";

        // Append au fichier
        file_put_contents($alertFile, $content, FILE_APPEND);

        $io->text(sprintf('✓ Alertes écrites dans : %s', self::ALERT_FILE));
    }
}
