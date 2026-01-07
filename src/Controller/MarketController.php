<?php

namespace App\Controller;

use App\Repository\ItemEntityRepository;
use App\Repository\GuildStockRepository;
use App\Service\PaxDeiClient;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class MarketController extends AbstractController
{
    #[Route('/items/{map}', name: 'market_items', defaults: ['map' => 'inis_gallia'])]
    public function items(string $map, ItemEntityRepository $itemRepository, GuildStockRepository $guildStockRepo, PaxDeiClient $client): Response
    {
        // Vérifier que la map existe
        if (!in_array($map, PaxDeiClient::getMaps())) {
            $map = 'inis_gallia'; // Fallback
        }
        
        // Récupérer les items depuis la base de données
        $dbItems = $itemRepository->findAllWithCategory();
        
        // Récupérer les IDs des items en stock
        $stockedItemIds = $guildStockRepo->getStockedItemIds();
        
        // Trier : reliques non stockées en premier, puis par rareté
        usort($dbItems, function($a, $b) use ($stockedItemIds) {
            $isRelicA = $a->getCategory() && $a->getCategory()->getName() === 'Reliques';
            $isRelicB = $b->getCategory() && $b->getCategory()->getName() === 'Reliques';
            $isStockedA = in_array($a->getId(), $stockedItemIds);
            $isStockedB = in_array($b->getId(), $stockedItemIds);
            
            // Reliques non stockées en premier
            if ($isRelicA && !$isStockedA && (!$isRelicB || $isStockedB)) {
                return -1;
            }
            if ($isRelicB && !$isStockedB && (!$isRelicA || $isStockedA)) {
                return 1;
            }
            
            // Ensuite par rareté
            $qualityOrder = ['rare' => 1, 'uncommon' => 2, 'common' => 3];
            $orderA = $qualityOrder[$a->getQuality()] ?? 99;
            $orderB = $qualityOrder[$b->getQuality()] ?? 99;
            return $orderA - $orderB;
        });
        
        $listingCounts = $client->getListingCountsByItemAndRegion($map);
        
        // Récupérer toutes les régions de la map actuelle
        $regions = PaxDeiClient::getRegions($map);
        $regions = array_map('ucfirst', $regions);
        sort($regions);
        
        // Récupérer toutes les catégories uniques
        $categories = [];
        foreach ($dbItems as $item) {
            if ($item->getCategory()) {
                $categories[] = $item->getCategory()->getName();
            }
        }
        $categories = array_unique($categories);
        sort($categories);
        
        return $this->render('market/items.html.twig', [
            'items' => $dbItems,
            'listingCounts' => $listingCounts,
            'regions' => $regions,
            'categories' => $categories,
            'currentMap' => $map,
            'availableMaps' => PaxDeiClient::getMaps(),
            'stockedItemIds' => $stockedItemIds,
        ]);
    }

    #[Route('/categories', name: 'market_categories')]
    public function categories(ItemEntityRepository $itemRepository): Response
    {
        $dbItems = $itemRepository->findAllWithCategory();
        
        // Grouper les items par catégorie et compter
        $categoryStats = [];
        foreach ($dbItems as $item) {
            $category = $item->getCategory() ? $item->getCategory()->getName() : 'Sans catégorie';
            if (!isset($categoryStats[$category])) {
                $categoryStats[$category] = [
                    'count' => 0,
                    'items' => []
                ];
            }
            $categoryStats[$category]['count']++;
            // Garder quelques exemples pour la visualisation
            if (count($categoryStats[$category]['items']) < 5) {
                $categoryStats[$category]['items'][] = [
                    'name' => $item->getName()['Fr'] ?? $item->getName()['En'] ?? '',
                    'iconPath' => $item->getIconPath(),
                    'url' => $item->getUrl()
                ];
            }
        }
        
        // Trier par nombre d'items décroissant
        uasort($categoryStats, function($a, $b) {
            return $b['count'] - $a['count'];
        });
        
        return $this->render('market/categories.html.twig', [
            'categoryStats' => $categoryStats,
            'totalItems' => count($dbItems)
        ]);
    }

    #[Route('/items/{itemId}/region/{region}/{map}', name: 'market_item_region_details', defaults: ['map' => 'inis_gallia'])]
    public function itemRegionDetails(string $itemId, string $region, string $map, Request $request, ItemEntityRepository $itemRepository, PaxDeiClient $client): Response
    {
        $listings = $client->getListingsByItemAndRegion($itemId, $region, $map);
        
        // Trouver l'item correspondant dans la BDD
        $item = $itemRepository->findByExternalId($itemId);
        
        // Récupérer l'URL de retour si elle existe
        $returnUrl = $request->query->get('returnUrl', $this->generateUrl('market_items'));
        
        return $this->render('market/item_region_details.html.twig', [
            'listings' => $listings,
            'item' => $item,
            'region' => $region,
            'returnUrl' => $returnUrl
        ]);
    }

    #[Route('/items/{itemId}/all-regions/{map}', name: 'market_item_all_regions', defaults: ['map' => 'inis_gallia'])]
    public function itemAllRegions(string $itemId, string $map, Request $request, ItemEntityRepository $itemRepository, PaxDeiClient $client): Response
    {
        // Trouver l'item correspondant dans la BDD
        $item = $itemRepository->findByExternalId($itemId);
        
        // Récupérer les listings par région pour la map spécifiée
        $listingsByRegion = [];
        $listingCounts = $client->getListingCountsByItemAndRegion($map);
        
        if (isset($listingCounts[$itemId])) {
            foreach ($listingCounts[$itemId] as $region => $count) {
                if ($count > 0) {
                    $listingsByRegion[$region] = $client->getListingsByItemAndRegion($itemId, $region, $map);
                }
            }
        }
        
        // Récupérer l'URL de retour si elle existe
        $returnUrl = $request->query->get('returnUrl', $this->generateUrl('market_items'));
        
        return $this->render('market/item_all_regions.html.twig', [
            'item' => $item,
            'listingsByRegion' => $listingsByRegion,
            'returnUrl' => $returnUrl
        ]);
    }
}
