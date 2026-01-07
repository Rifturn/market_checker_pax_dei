<?php

namespace App\Controller;

use App\Entity\UserView;
use App\Form\UserViewType;
use App\Repository\ItemEntityRepository;
use App\Repository\GuildStockRepository;
use App\Service\PaxDeiClient;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
class ProfileController extends AbstractController
{
    #[Route('/profile', name: 'app_profile')]
    public function index(): Response
    {
        return $this->render('profile/index.html.twig', [
            'user' => $this->getUser(),
        ]);
    }

    #[Route('/profile/view/new', name: 'app_profile_view_new')]
    public function newView(Request $request, EntityManagerInterface $em): Response
    {
        $view = new UserView();
        $view->setUser($this->getUser());

        $form = $this->createForm(UserViewType::class, $view);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($view);
            $em->flush();

            $this->addFlash('success', 'Vue créée avec succès !');
            return $this->redirectToRoute('app_profile');
        }

        return $this->render('profile/view_form.html.twig', [
            'form' => $form->createView(),
            'view' => $view,
        ]);
    }

    #[Route('/profile/view/{id}/edit', name: 'app_profile_view_edit')]
    public function editView(UserView $view, Request $request, EntityManagerInterface $em): Response
    {
        if ($view->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        $form = $this->createForm(UserViewType::class, $view);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();

            $this->addFlash('success', 'Vue modifiée avec succès !');
            return $this->redirectToRoute('app_profile');
        }

        return $this->render('profile/view_form.html.twig', [
            'form' => $form->createView(),
            'view' => $view,
        ]);
    }

    #[Route('/profile/view/{id}/delete', name: 'app_profile_view_delete', methods: ['POST'])]
    public function deleteView(UserView $view, Request $request, EntityManagerInterface $em): Response
    {
        if ($view->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        if ($this->isCsrfTokenValid('delete' . $view->getId(), $request->request->get('_token'))) {
            $em->remove($view);
            $em->flush();

            $this->addFlash('success', 'Vue supprimée avec succès !');
        }

        return $this->redirectToRoute('app_profile');
    }

    #[Route('/view/{id}/{map}', name: 'app_view_show', defaults: ['map' => 'inis_gallia'])]
    public function showView(string $map, UserView $view, ItemEntityRepository $itemRepo, GuildStockRepository $guildStockRepo, PaxDeiClient $client): Response
    {
        if ($view->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        // Vérifier que la map existe
        if (!in_array($map, PaxDeiClient::getMaps())) {
            $map = 'inis_gallia'; // Fallback
        }

        // Récupérer les items filtrés
        $filteredItems = [];
        
        // Items spécifiques sélectionnés
        foreach ($view->getItems() as $item) {
            $filteredItems[$item->getId()] = $item;
        }

        // Items des catégories sélectionnées
        foreach ($view->getCategories() as $category) {
            foreach ($category->getItems() as $item) {
                $filteredItems[$item->getId()] = $item;
            }
        }

        $filteredItems = array_values($filteredItems);
        
        // Récupérer les IDs des items en stock
        $stockedItemIds = $guildStockRepo->getStockedItemIds();

        // Trier : reliques non stockées en premier, puis par rareté
        usort($filteredItems, function($a, $b) use ($stockedItemIds) {
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

        // Récupérer les données de marché pour la map sélectionnée
        $listingCounts = $client->getListingCountsByItemAndRegion($map);
        
        // Récupérer les prix minimaux
        $minPrices = $client->getMinPricesByItem($map);
        
        // Récupérer toutes les régions de la map actuelle
        $regions = PaxDeiClient::getRegions($map);
        $regions = array_map('ucfirst', $regions);
        sort($regions);

        return $this->render('profile/view_show.html.twig', [
            'view' => $view,
            'items' => $filteredItems,
            'listingCounts' => $listingCounts,
            'regions' => $regions,
            'currentMap' => $map,
            'availableMaps' => PaxDeiClient::getMaps(),
            'stockedItemIds' => $stockedItemIds,
            'minPrices' => $minPrices,
        ]);
    }
}
