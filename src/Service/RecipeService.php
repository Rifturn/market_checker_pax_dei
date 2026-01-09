<?php

namespace App\Service;

use App\Entity\ItemEntity;
use App\Repository\ItemEntityRepository;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class RecipeService
{
    public function __construct(
        private HttpClientInterface $httpClient,
        private ItemEntityRepository $itemRepository,
    ) {
    }

    /**
     * Récupère la recette complète d'un item via l'API
     * @return array{inputs: array, outputs: array}|null
     */
    public function getRecipeFromApi(ItemEntity $item): ?array
    {
        // Utiliser l'URL API déjà calculée par la commande UpdateItemTypeCommand
        $apiUrl = $item->getUrlApi();
        
        // Si pas d'URL API, essayer de la construire depuis l'URL
        if (!$apiUrl) {
            $url = $item->getUrl();
            if ($url) {
                $apiUrl = $this->buildApiUrl($url);
            }
        }
        
        if (!$apiUrl) {
            return null;
        }

        try {
            $response = $this->httpClient->request('GET', $apiUrl);
            $data = $response->toArray();

            // Chercher dans sources[0].itemIngredients
            if (isset($data['sources'][0]['itemIngredients']) && !empty($data['sources'][0]['itemIngredients'])) {
                // Retourner directement les ingrédients
                return [
                    'inputs' => $data['sources'][0]['itemIngredients'],
                    'outputs' => []
                ];
            }

            return null;
        } catch (\Exception $e) {
            // Log l'erreur si besoin
            return null;
        }
    }

    /**
     * Construit l'URL API depuis l'URL gaming.tools (même logique que UpdateItemTypeCommand)
     */
    private function buildApiUrl(string $url): ?string
    {
        // Pattern: https://paxdei.gaming.tools/{type_pluriel}/{item_name}
        $pattern = '#https://paxdei\.gaming\.tools/([^/]+)/(.+)$#';
        
        if (preg_match($pattern, $url, $matches)) {
            $typePlural = $matches[1];
            $itemName = $matches[2];
            
            // Convertir le pluriel en singulier
            $type = $this->pluralToSingular($typePlural);
            
            // Construire l'URL API
            return sprintf(
                'https://data-cdn.gaming.tools/paxdei/data/fr/%s/%s.json',
                $type,
                $itemName
            );
        }
        
        return null;
    }

    /**
     * Convertit un nom au pluriel en singulier (même logique que UpdateItemTypeCommand)
     */
    private function pluralToSingular(string $plural): string
    {
        $rules = [
            'consumables' => 'consumable',
            'weapons' => 'weapon',
            'wearables' => 'wearable',
            'wieldables' => 'wieldable',
            'equipments' => 'equipment',
            'relics' => 'relic',
            'armors' => 'armor',
            'tools' => 'tool',
            'resources' => 'resource',
            'materials' => 'material',
            'ingredients' => 'ingredient',
            'recipes' => 'recipe',
        ];
        
        if (isset($rules[$plural])) {
            return $rules[$plural];
        }
        
        // Règle générique : retirer le 's' final
        if (str_ends_with($plural, 's')) {
            return substr($plural, 0, -1);
        }
        
        return $plural;
    }

    /**
     * Parse les ingrédients d'une recette et retourne les items trouvés en DB
     * @param array $recipe
     * @return array
     */
    public function parseRecipeIngredients(array $recipe): array
    {
        $ingredients = [];

        // Récupérer les inputs de la recette
        if (!isset($recipe['inputs']) || empty($recipe['inputs'])) {
            return [];
        }

        foreach ($recipe['inputs'] as $input) {
            // Dans sources[0].itemIngredients, la structure est : entity.id contient l'externalId
            $ingredientId = null;
            $ingredientName = 'Inconnu';
            $quantity = 1;

            if (isset($input['entity']['id'])) {
                $ingredientId = $input['entity']['id'];
                $ingredientName = $input['entity']['name'] ?? $ingredientId;
            }

            if (!$ingredientId) {
                continue;
            }

            // La quantité est dans 'count' et non 'quantity'
            if (isset($input['count'])) {
                $quantity = (int) $input['count'];
            }

            // Chercher l'item en DB par externalId
            $foundItem = $this->itemRepository->findByExternalId($ingredientId);

            $ingredients[] = [
                'externalId' => $ingredientId,
                'name' => $ingredientName,
                'quantity' => $quantity,
                'item' => $foundItem, // peut être null si non trouvé en DB
            ];
        }

        return $ingredients;
    }

    /**
     * Récupère tous les ingrédients pour un item avec les données enrichies
     * @return array
     */
    public function getIngredientsForItem(ItemEntity $item): array
    {
        $recipe = $this->getRecipeFromApi($item);
        
        if (!$recipe) {
            return [];
        }

        return $this->parseRecipeIngredients($recipe);
    }
}
