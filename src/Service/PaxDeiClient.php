<?php

namespace App\Service;

use App\Entity\Item;
use App\Entity\Listing;

class PaxDeiClient
{
    private const ITEMS_URL = 'https://data-cdn.gaming.tools/paxdei/market/items.json';
    private const BASE_URL = 'https://data-cdn.gaming.tools/paxdei/market/demeter/';
    
    private const MAPS = [
        'merrie' => ['shire', 'yarborn', 'caster', 'gael', 'nene', 'ulaid', 'wiht', 'ardbog', 'down', 'bearm'],
        'ancien' => ['libornes', 'lavedan', 'salias', 'tursan', 'volvestre', 'tolosa', 'armanhac', 'maremna', 'gravas', 'astarac'],
        'inis_gallia' => ['atigny', 'javerdus', 'morvan', 'jura', 'aras', 'langres', 'nones', 'trecassis', 'vitry', 'ardennes'],
        'kerys' => ['tremen', 'llydaw', 'pladenn', 'aven', 'ewyas', 'dreger', 'retz', 'dolavon', 'vanes', 'bronyr'],
    ];
    
    public static function getMaps(): array
    {
        return array_keys(self::MAPS);
    }
    
    public static function getRegions(string $map): array
    {
        return self::MAPS[$map] ?? [];
    }
    
    public static function getAllMapsWithRegions(): array
    {
        return self::MAPS;
    }

    public function fetchAllItems(): array
    {
        $json = @file_get_contents(self::ITEMS_URL);
        if ($json === false) {
            throw new \RuntimeException('Failed to fetch items list');
        }
        $data = json_decode($json, true);
        if (!is_array($data)) {
            throw new \RuntimeException('Invalid JSON structure');
        }
        $items = [];
        foreach ($data as $itemData) {
            $url = $itemData['url'] ?? '';
            $iconPath = $itemData['iconPath'] ?? '';
            // Utiliser iconPath en priorité pour la catégorisation
            $category = $this->extractCategoryFromIconPath($iconPath);
            $items[] = new Item(
                $itemData['id'] ?? '',
                $itemData['name'] ?? [],
                $iconPath,
                $url,
                $category
            );
        }
        return $items;
    }

    private function extractCategoryFromUrl(string $url): string
    {
        // Format: https://paxdei.gaming.tools/CATEGORIE/IDOBJ
        if (preg_match('#/([^/]+)/[^/]+$#', $url, $matches)) {
            return ucfirst($matches[1]);
        }
        return 'Uncategorized';
    }

    /**
     * Extract intelligent category from iconPath
     */
    private function extractCategoryFromIconPath(string $iconPath): string
    {
        // Patterns pour identifier les catégories depuis le chemin de l'icône
        $patterns = [
            // Consommables
            '/consumables\/bottles/' => 'Potions & Sirops',
            '/consumables\/cookedfood\/bread/' => 'Pain',
            '/consumables\/cookedfood\/raisins/' => 'Fruits secs',
            '/consumables\/cookedfood\/roast/' => 'Viandes rôties',
            '/consumables\/cookedfood\/stew/' => 'Ragoûts',
            '/consumables\/cookedfood/' => 'Nourriture cuisinée',
            '/consumables\/potions/' => 'Potions',
            '/consumables\/vials/' => 'Fioles',
            '/consumables\/sacredoil/' => 'Huiles sacrées',
            
            // Ressources naturelles
            '/nature\/resources\/mushrooms_poisonous/' => 'Champignons vénéneux',
            '/nature\/resources\/mushrooms/' => 'Champignons',
            '/nature\/resources\/flowers/' => 'Fleurs',
            '/nature\/resources\/herbscooking/' => 'Herbes culinaires',
            '/nature\/resources\/berries/' => 'Baies',
            '/nature\/resources\/grasscloth\/barley/' => 'Orge',
            '/nature\/resources\/grasscloth\/oat/' => 'Avoine',
            '/nature\/resources\/grasscloth\/rye/' => 'Seigle',
            '/nature\/resources\/grasscloth\/wheat/' => 'Blé',
            '/nature\/resources\/grasscloth/' => 'Céréales',
            '/nature\/resources\/gatherabledebris/' => 'Pierres brutes',
            
            // Objets de construction (building props)
            '/da_buildingprops\/.*\/lighting_and_decorations/' => 'Éclairage & Décorations',
            '/da_buildingprops\/.*\/storage/' => 'Stockage',
            '/da_buildingprops\/.*\/furniture/' => 'Meubles',
            '/da_buildingprops\/.*\/wall_decos/' => 'Décorations murales',
            '/da_buildingprops\/.*\/doors_and_shutters/' => 'Portes & Volets',
            '/da_buildingprops\/.*\/signs/' => 'Enseignes',
            '/da_buildingprops\/.*\/tableware/' => 'Vaisselle',
            '/da_buildingprops\/.*\/planters/' => 'Jardinières',
            '/da_buildingprops/' => 'Objets de construction',
            
            // Matériaux de craft
            '/craftingproducts\/ingots/' => 'Lingots',
            '/craftingproducts\/animalbones/' => 'Os d\'animaux',
            '/craftingproducts\/animalcarcasses/' => 'Carcasses',
            '/craftingproducts\/animalinnards/' => 'Abats',
            '/craftingproducts\/meatchunks/' => 'Viande découpée',
            '/craftingproducts\/breadanddrinkproducts/' => 'Ingrédients cuisine',
            '/craftingproducts\/amberextract/' => 'Extraits d\'ambre',
            '/craftingproducts\/blessedpowder/' => 'Poudres bénies',
            '/craftingproducts\/gambesoncomponents/' => 'Composants gambison',
            '/craftingproducts\/icons\/.*arrowhead/' => 'Pointes de flèches',
            '/craftingproducts\/icons\/.*_blade/' => 'Lames',
            '/craftingproducts\/icons\/.*button/' => 'Boutons',
            '/craftingproducts\/icons\/.*leather/' => 'Cuir travaillé',
            '/craftingproducts\/icons\/.*armor/' => 'Composants armure',
            '/craftingproducts\/icons\/.*string/' => 'Fils & Cordes',
            '/craftingproducts\/icons\/.*wood/' => 'Bois travaillé',
            '/craftingproducts\/icons/' => 'Composants craft',
            '/craftingproducts/' => 'Matériaux craftés',
            
            // Équipement (wearables)
            '/game\/inventory\/icons\/.*_cloth_chest/' => 'Armure tissu (torse)',
            '/game\/inventory\/icons\/.*_cloth_legs/' => 'Armure tissu (jambes)',
            '/game\/inventory\/icons\/.*_cloth_feet/' => 'Armure tissu (pieds)',
            '/game\/inventory\/icons\/.*_cloth_hands/' => 'Armure tissu (mains)',
            '/game\/inventory\/icons\/.*_cloth_head/' => 'Armure tissu (tête)',
            '/game\/inventory\/icons\/.*_cloth_arms/' => 'Armure tissu (bras)',
            '/game\/inventory\/icons\/.*_leather_chest/' => 'Armure cuir (torse)',
            '/game\/inventory\/icons\/.*_leather_legs/' => 'Armure cuir (jambes)',
            '/game\/inventory\/icons\/.*_leather_feet/' => 'Armure cuir (pieds)',
            '/game\/inventory\/icons\/.*_leather_hands/' => 'Armure cuir (mains)',
            '/game\/inventory\/icons\/.*_leather_head/' => 'Armure cuir (tête)',
            '/game\/inventory\/icons\/.*_leather_arms/' => 'Armure cuir (bras)',
            '/game\/inventory\/icons\/.*_metal_chest/' => 'Armure métal (torse)',
            '/game\/inventory\/icons\/.*_metal_legs/' => 'Armure métal (jambes)',
            '/game\/inventory\/icons\/.*_metal_feet/' => 'Armure métal (pieds)',
            '/game\/inventory\/icons\/.*_metal_hands/' => 'Armure métal (mains)',
            '/game\/inventory\/icons\/.*_metal_head/' => 'Armure métal (tête)',
            '/game\/inventory\/icons\/.*_back_/' => 'Capes & Manteaux',
            '/game\/inventory\/icons\/.*_hat_jewellery/' => 'Bijoux de tête',
            '/game\/inventory\/icons\/.*_neck_jewellery/' => 'Colliers & Amulettes',
            '/game\/inventory\/icons\/.*wieldable.*staff/' => 'Bâtons',
            '/game\/inventory\/icons\/.*wieldable.*bow/' => 'Arcs',
            '/game\/inventory\/icons\/.*wieldable.*sword/' => 'Épées',
            '/game\/inventory\/icons\/.*wieldable.*mace/' => 'Masses',
            '/game\/inventory\/icons\/.*wieldable.*axe/' => 'Haches',
            '/game\/inventory\/icons\/.*wieldable.*spear/' => 'Lances',
            '/game\/inventory\/icons\/.*wieldable.*shield/' => 'Boucliers',
            '/game\/inventory\/icons\/.*ranged/' => 'Projectiles',
            
            // Outils de crafters
            '/da_crafters\/icons\/menu_tab_crafters/' => 'Outils de métier',
            '/itemunlockparchments/' => 'Parchemins de déblocage',
            
            // Reliques
            '/enemydrops\/relics/' => 'Reliques',
            
            // Pièces de bâtiment
            '/modularbuildings\/da_buildingpieces/' => 'Pièces modulaires',
        ];
        
        foreach ($patterns as $pattern => $category) {
            if (preg_match('|' . $pattern . '|i', $iconPath)) {
                return $category;
            }
        }
        
        // Fallback: essayer d'extraire quelque chose d'utile du chemin
        if (preg_match('/\/([^\/]+)\/[^\/]+\.(webp|png|jpg)$/i', $iconPath, $matches)) {
            return ucfirst(str_replace('_', ' ', $matches[1]));
        }
        
        return 'Autre';
    }

    public function fetchAllListings(?string $map = null): array
    {
        $map = $map ?? 'inis_gallia'; // Défaut
        $regions = self::getRegions($map);
        $allListings = [];
        
        foreach ($regions as $region) {
            $url = self::BASE_URL . $map . '/' . $region . '.json';
            $json = @file_get_contents($url);
            
            if ($json === false) {
                continue; // Skip si l'URL échoue
            }
            
            $data = json_decode($json, true);
            if (!is_array($data)) {
                continue;
            }
            
            foreach ($data as $listingData) {
                $allListings[] = new Listing(
                    $listingData['id'] ?? '',
                    $listingData['item_id'] ?? '',
                    $listingData['quantity'] ?? 0,
                    $listingData['price'] ?? 0,
                    ucfirst($region),
                    $listingData['last_seen'] ?? 0
                );
            }
        }
        
        return $allListings;
    }

    public function getListingCountsByItemAndRegion(?string $map = null): array
    {
        $listings = $this->fetchAllListings($map);
        $counts = [];
        
        foreach ($listings as $listing) {
            $itemId = $listing->getItemId();
            $region = $listing->getZone();
            
            if (!isset($counts[$itemId])) {
                $counts[$itemId] = [];
            }
            
            if (!isset($counts[$itemId][$region])) {
                $counts[$itemId][$region] = 0;
            }
            
            $counts[$itemId][$region]++;
        }
        
        return $counts;
    }

    public function getMinPricesByItem(?string $map = null): array
    {
        $listings = $this->fetchAllListings($map);
        $minPrices = [];
        
        foreach ($listings as $listing) {
            $itemId = $listing->getItemId();
            $price = $listing->getPrice();
            
            if (!isset($minPrices[$itemId]) || $price < $minPrices[$itemId]) {
                $minPrices[$itemId] = $price;
            }
        }
        
        return $minPrices;
    }

    public function getListingsByItemAndRegion(string $itemId, string $region, ?string $map = null): array
    {
        $allListings = $this->fetchAllListings($map);
        
        return array_filter($allListings, function($listing) use ($itemId, $region) {
            return $listing->getItemId() === $itemId && $listing->getZone() === $region;
        });
    }

    private function extractRegionFromUrl(string $url): string
    {
        // Format: .../ardennes.json -> ardennes
        if (preg_match('#/([^/]+)\.json$#', $url, $matches)) {
            return ucfirst($matches[1]);
        }
        return 'Unknown';
    }
}
