<?php

namespace App\Controller;

use App\Entity\EquipmentSet;
use App\Entity\UserView;
use App\Repository\EquipmentSetRepository;
use App\Repository\ItemEntityRepository;
use App\Repository\ItemRecipeRepository;
use App\Repository\SpellItemRepository;
use App\Service\RecipeService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/equipment-sets')]
class EquipmentSetController extends AbstractController
{
    #[Route('', name: 'equipment_set_index')]
    public function index(EquipmentSetRepository $setRepository): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $sets = $setRepository->findByUser($user->getId());

        return $this->render('equipment_set/index.html.twig', [
            'sets' => $sets,
        ]);
    }

    #[Route('/new', name: 'equipment_set_new')]
    public function new(Request $request, ItemEntityRepository $itemRepository, SpellItemRepository $spellItemRepository, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        if ($request->isMethod('POST')) {
            $set = new EquipmentSet();
            $set->setName($request->request->get('name', 'Nouveau set'));
            $set->setUser($user);

            // Récupérer les IDs des items sélectionnés
            $slots = ['helmet', 'gloves', 'bracers', 'chest', 'legs', 'boots', 'mainHand', 'offHand'];
            
            foreach ($slots as $slot) {
                $itemId = $request->request->get($slot);
                if ($itemId) {
                    $item = $itemRepository->find($itemId);
                    if ($item) {
                        $setter = 'set' . ucfirst($slot);
                        $set->$setter($item);
                    }
                }
            }

            $em->persist($set);
            $em->flush();

            $this->addFlash('success', 'Set d\'équipement créé avec succès !');
            return $this->redirectToRoute('equipment_set_show', ['id' => $set->getId()]);
        }

        // Récupérer les items pour les différents slots
        $wearables = $itemRepository->findBy(['type' => 'wearable'], ['quality' => 'DESC']);
        $wieldables = $itemRepository->findBy(['type' => 'wieldable'], ['quality' => 'DESC']);

        // Grouper les wearables par slot
        $wearablesBySlot = [];
        foreach ($wearables as $wearable) {
            $slot = $wearable->getSlotCategory();
            if ($slot) {
                if (!isset($wearablesBySlot[$slot])) {
                    $wearablesBySlot[$slot] = [];
                }
                $wearablesBySlot[$slot][] = $wearable;
            }
        }

        // Charger les spells pour chaque item
        $itemsWithSpells = [];
        foreach (array_merge($wearables, $wieldables) as $item) {
            $spellItems = $spellItemRepository->findByItem($item->getId());
            $itemsWithSpells[$item->getId()] = [
                'item' => $item,
                'spells' => $spellItems
            ];
        }

        return $this->render('equipment_set/new.html.twig', [
            'wearables' => $wearables,
            'wieldables' => $wieldables,
            'wearablesBySlot' => $wearablesBySlot,
            'itemsWithSpells' => $itemsWithSpells,
        ]);
    }

    #[Route('/{id}', name: 'equipment_set_show', requirements: ['id' => '\d+'])]
    public function show(EquipmentSet $set, SpellItemRepository $spellItemRepository): Response
    {
        // Vérifier que l'utilisateur est propriétaire du set
        if ($set->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        // Récupérer les sorts associés à chaque item
        $spells = [];
        $slots = ['helmet', 'gloves', 'bracers', 'chest', 'legs', 'boots', 'mainHand', 'offHand'];
        
        foreach ($slots as $slot) {
            $getter = 'get' . ucfirst($slot);
            $item = $set->$getter();
            
            if ($item) {
                $itemSpells = $spellItemRepository->findByItem($item->getId());
                if (!empty($itemSpells)) {
                    $spells[$slot] = $itemSpells;
                }
            }
        }

        return $this->render('equipment_set/show.html.twig', [
            'set' => $set,
            'spells' => $spells,
        ]);
    }

    #[Route('/{id}/edit', name: 'equipment_set_edit', requirements: ['id' => '\d+'])]
    public function edit(EquipmentSet $set, Request $request, ItemEntityRepository $itemRepository, SpellItemRepository $spellItemRepository, EntityManagerInterface $em): Response
    {
        // Vérifier que l'utilisateur est propriétaire du set
        if ($set->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        if ($request->isMethod('POST')) {
            $set->setName($request->request->get('name', $set->getName()));

            // Récupérer les IDs des items sélectionnés
            $slots = ['helmet', 'gloves', 'bracers', 'chest', 'legs', 'boots', 'mainHand', 'offHand'];
            
            foreach ($slots as $slot) {
                $itemId = $request->request->get($slot);
                $setter = 'set' . ucfirst($slot);
                
                if ($itemId) {
                    $item = $itemRepository->find($itemId);
                    $set->$setter($item);
                } else {
                    $set->$setter(null);
                }
            }

            $set->setUpdatedAt(new \DateTime());
            $em->flush();

            $this->addFlash('success', 'Set d\'équipement mis à jour avec succès !');
            return $this->redirectToRoute('equipment_set_show', ['id' => $set->getId()]);
        }

        // Récupérer les items pour les différents slots
        $wearables = $itemRepository->findBy(['type' => 'wearable'], ['quality' => 'DESC']);
        $wieldables = $itemRepository->findBy(['type' => 'wieldable'], ['quality' => 'DESC']);

        // Grouper les wearables par slot
        $wearablesBySlot = [];
        foreach ($wearables as $wearable) {
            $slot = $wearable->getSlotCategory();
            if ($slot) {
                if (!isset($wearablesBySlot[$slot])) {
                    $wearablesBySlot[$slot] = [];
                }
                $wearablesBySlot[$slot][] = $wearable;
            }
        }

        // Charger les spells pour chaque item
        $itemsWithSpells = [];
        foreach (array_merge($wearables, $wieldables) as $item) {
            $spellItems = $spellItemRepository->findByItem($item->getId());
            $itemsWithSpells[$item->getId()] = [
                'item' => $item,
                'spells' => $spellItems
            ];
        }

        return $this->render('equipment_set/edit.html.twig', [
            'set' => $set,
            'wearables' => $wearables,
            'wieldables' => $wieldables,
            'wearablesBySlot' => $wearablesBySlot,
            'itemsWithSpells' => $itemsWithSpells,
        ]);
    }

    #[Route('/{id}/delete', name: 'equipment_set_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function delete(EquipmentSet $set, EntityManagerInterface $em): Response
    {
        // Vérifier que l'utilisateur est propriétaire du set
        if ($set->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        $em->remove($set);
        $em->flush();

        $this->addFlash('success', 'Set d\'équipement supprimé avec succès !');
        return $this->redirectToRoute('equipment_set_index');
    }

    #[Route('/{id}/create-shopping-view', name: 'equipment_set_create_shopping_view', requirements: ['id' => '\d+'])]
    public function createShoppingView(EquipmentSet $set, RecipeService $recipeService): Response
    {
        // Vérifier que l'utilisateur est propriétaire du set
        if ($set->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        // Récupérer tous les items du set avec leurs slots
        $slots = [
            'helmet' => '🪖 Casque',
            'gloves' => '🧤 Gants',
            'bracers' => '🛡️ Brassards',
            'chest' => '🎽 Torse',
            'legs' => '👖 Jambes',
            'boots' => '👢 Bottes',
            'mainHand' => '⚔️ Main principale',
            'offHand' => '🛡️ Main secondaire'
        ];
        
        $itemsData = [];
        
        foreach ($slots as $slot => $slotLabel) {
            $getter = 'get' . ucfirst($slot);
            $item = $set->$getter();
            
            if ($item) {
                $itemName = $item->getName()['Fr'] ?? $item->getExternalId();
                
                // Récupérer la recette complète via l'API
                $ingredients = $recipeService->getIngredientsForItem($item);
                
                if (!empty($ingredients)) {
                    // L'item a une recette complète
                    $itemsData[] = [
                        'slot' => $slotLabel,
                        'item' => $item,
                        'itemName' => $itemName,
                        'hasRecipe' => true,
                        'ingredients' => $ingredients
                    ];
                } else {
                    // L'item n'a pas de recette, on l'affiche directement
                    $itemsData[] = [
                        'slot' => $slotLabel,
                        'item' => $item,
                        'itemName' => $itemName,
                        'hasRecipe' => false,
                        'ingredients' => []
                    ];
                }
            }
        }

        return $this->render('equipment_set/create_shopping_view.html.twig', [
            'set' => $set,
            'itemsData' => $itemsData,
        ]);
    }

    #[Route('/{id}/save-shopping-view', name: 'equipment_set_save_shopping_view', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function saveShoppingView(
        EquipmentSet $set, 
        Request $request, 
        EntityManagerInterface $em,
        ItemEntityRepository $itemRepository
    ): Response {
        // Vérifier que l'utilisateur est propriétaire du set
        if ($set->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        $viewName = $request->request->get('viewName');
        $selectedItemIds = $request->request->all('items') ?? [];

        // Validation
        if (empty($viewName)) {
            $this->addFlash('error', 'Le nom de la vue est obligatoire.');
            return $this->redirectToRoute('equipment_set_create_shopping_view', ['id' => $set->getId()]);
        }

        if (empty($selectedItemIds)) {
            $this->addFlash('error', 'Veuillez sélectionner au moins un ingrédient.');
            return $this->redirectToRoute('equipment_set_create_shopping_view', ['id' => $set->getId()]);
        }

        // Dédupliquer les IDs (car un même ingrédient peut apparaître plusieurs fois)
        $uniqueItemIds = array_unique($selectedItemIds);

        // Créer la vue
        $userView = new UserView();
        $userView->setName($viewName);
        $userView->setUser($this->getUser());

        // Ajouter les items sélectionnés
        foreach ($uniqueItemIds as $itemId) {
            $item = $itemRepository->find($itemId);
            if ($item) {
                $userView->addItem($item);
            }
        }

        $em->persist($userView);
        $em->flush();

        $this->addFlash('success', sprintf('La vue "%s" a été créée avec succès avec %d ingrédients uniques.', $viewName, count($uniqueItemIds)));
        
        // Rediriger vers la liste des vues ou vers la vue créée
        return $this->redirectToRoute('equipment_set_show', ['id' => $set->getId()]);
    }

    #[Route('/api/item/{id}/spells', name: 'api_item_spells', requirements: ['id' => '\d+'])]
    public function getItemSpells(int $id, SpellItemRepository $spellItemRepository): JsonResponse
    {
        $spellItems = $spellItemRepository->findByItem($id);
        
        $data = [];
        foreach ($spellItems as $spellItem) {
            $spell = $spellItem->getSpell();
            $data[] = [
                'id' => $spell->getId(),
                'name' => $spell->getName(),
                'externalId' => $spell->getExternalId(),
                'iconPath' => $spell->getIconPath(),
            ];
        }

        return new JsonResponse($data);
    }
}
