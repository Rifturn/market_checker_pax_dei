<?php

namespace App\Controller\Admin;

use App\Entity\GuildStock;
use App\Form\GuildStockType;
use App\Repository\GuildStockRepository;
use App\Repository\ItemRecipeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/guild-stock')]
#[IsGranted('ROLE_ADMIN')]
class GuildStockController extends AbstractController
{
    #[Route('/', name: 'admin_guild_stock_index')]
    public function index(GuildStockRepository $stockRepository, ItemRecipeRepository $recipeRepository): Response
    {
        $stocks = $stockRepository->findBy([], ['updatedAt' => 'DESC']);

        // Récupérer les recettes pour tous les items en stock
        $items = array_map(fn($stock) => $stock->getItem(), $stocks);
        $recipes = $recipeRepository->findByIngredients($items);
        
        // Indexer les recettes par item_id pour un accès facile
        $recipesByItem = [];
        foreach ($recipes as $recipe) {
            $recipesByItem[$recipe->getIngredient()->getId()] = $recipe;
        }

        return $this->render('admin/guild_stock/index.html.twig', [
            'stocks' => $stocks,
            'recipes' => $recipesByItem,
        ]);
    }

    #[Route('/new', name: 'admin_guild_stock_new')]
    public function new(Request $request, EntityManagerInterface $em, GuildStockRepository $stockRepository): Response
    {
        $stock = new GuildStock();
        $form = $this->createForm(GuildStockType::class, $stock, [
            'existing_stock' => $stockRepository->findAll()
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Vérifier si l'item existe déjà dans le stock
            $existingStock = $stockRepository->findOneBy(['item' => $stock->getItem()]);
            if ($existingStock) {
                $this->addFlash('error', 'Cette relique est déjà présente dans le stock. Veuillez la modifier plutôt.');
                return $this->redirectToRoute('admin_guild_stock_index');
            }

            $em->persist($stock);
            $em->flush();

            $this->addFlash('success', 'Relique ajoutée au stock de guilde !');
            return $this->redirectToRoute('admin_guild_stock_index');
        }

        return $this->render('admin/guild_stock/form.html.twig', [
            'form' => $form->createView(),
            'stock' => $stock,
        ]);
    }

    #[Route('/{id}/edit', name: 'admin_guild_stock_edit')]
    public function edit(GuildStock $stock, Request $request, EntityManagerInterface $em, GuildStockRepository $stockRepository): Response
    {
        $form = $this->createForm(GuildStockType::class, $stock, [
            'existing_stock' => $stockRepository->findAll()
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();

            $this->addFlash('success', 'Stock mis à jour !');
            return $this->redirectToRoute('admin_guild_stock_index');
        }

        return $this->render('admin/guild_stock/form.html.twig', [
            'form' => $form->createView(),
            'stock' => $stock,
        ]);
    }

    #[Route('/{id}/delete', name: 'admin_guild_stock_delete', methods: ['POST'])]
    public function delete(GuildStock $stock, Request $request, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete' . $stock->getId(), $request->request->get('_token'))) {
            $em->remove($stock);
            $em->flush();

            $this->addFlash('success', 'Relique supprimée du stock !');
        }

        return $this->redirectToRoute('admin_guild_stock_index');
    }
}
