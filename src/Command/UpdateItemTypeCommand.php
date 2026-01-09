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
    name: 'app:update-item-type',
    description: 'Met à jour le type, l\'URL API et les catégories des items depuis l\'API',
)]
class UpdateItemTypeCommand extends Command
{
    public function __construct(
        private ItemEntityRepository $itemRepository,
        private EntityManagerInterface $em,
        private HttpClientInterface $httpClient
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        
        $items = $this->itemRepository->findAll();
        $updatedType = 0;
        $updatedUrlApi = 0;
        $updatedCategories = 0;
        $errors = 0;
        $batchSize = 50;
        $processed = 0;
        
        $io->progressStart(count($items));
        
        foreach ($items as $item) {
            $url = $item->getUrl();
            
            if (!$url) {
                $io->progressAdvance();
                continue;
            }
            
            // Extraire le type depuis l'URL
            // Ex: https://paxdei.gaming.tools/consumables/activatable_drink_ale_resistancebuff_10
            // Pattern: https://paxdei.gaming.tools/{type_pluriel}/{item_name}
            $pattern = '#https://paxdei\.gaming\.tools/([^/]+)/(.+)$#';
            
            if (preg_match($pattern, $url, $matches)) {
                $typePlural = $matches[1];
                $itemName = $matches[2];
                
                // Convertir le pluriel en singulier
                $type = $this->pluralToSingular($typePlural);
                
                if ($item->getType() !== $type) {
                    $item->setType($type);
                    $updatedType++;
                }
                
                // Construire l'URL API
                $urlApi = sprintf(
                    'https://data-cdn.gaming.tools/paxdei/data/fr/%s/%s.json',
                    $type,
                    $itemName
                );
                
                if ($item->getUrlApi() !== $urlApi) {
                    $item->setUrlApi($urlApi);
                    $updatedUrlApi++;
                }
                
                // Récupérer les catégories depuis l'API
                try {
                    $response = $this->httpClient->request('GET', $urlApi);
                    $data = $response->toArray();
                    
                    if (isset($data['categories']) && is_array($data['categories'])) {
                        $armorCategory = null;
                        $slotCategory = null;
                        
                        foreach ($data['categories'] as $category) {
                            // Ex: "Category.Armor.Cloth"
                            if (str_contains($category, 'Category.Armor.') && !str_contains($category, '.Slot.')) {
                                $armorCategory = $category;
                            }
                            // Ex: "Category.Armor.Slot.Head"
                            if (str_contains($category, 'Category.Armor.Slot.')) {
                                $slotCategory = $category;
                            }
                        }
                        
                        if ($armorCategory && $item->getArmorCategory() !== $armorCategory) {
                            $item->setArmorCategory($armorCategory);
                            $updatedCategories++;
                        }
                        
                        if ($slotCategory && $item->getSlotCategory() !== $slotCategory) {
                            $item->setSlotCategory($slotCategory);
                            $updatedCategories++;
                        }
                    }
                } catch (\Exception $e) {
                    // Ignorer les erreurs API (item non trouvé, etc.)
                }
            } else {
                $errors++;
            }
            
            $processed++;
            
            // Flush par lot pour éviter de perdre les données en cas d'interruption
            if ($processed % $batchSize === 0) {
                $this->em->flush();
            }
            
            $io->progressAdvance();
        }
        
        // Flush final
        $this->em->flush();
        
        $io->progressFinish();
        
        $io->success(sprintf(
            'Mise à jour terminée : %d types, %d URLs API, %d catégories sur %d items (erreurs: %d).',
            $updatedType,
            $updatedUrlApi,
            $updatedCategories,
            count($items),
            $errors
        ));

        return Command::SUCCESS;
    }
    
    /**
     * Convertit un nom au pluriel en singulier de manière simple
     */
    private function pluralToSingular(string $plural): string
    {
        // Règles simples pour les types connus
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
}
