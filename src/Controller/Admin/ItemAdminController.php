<?php

namespace App\Controller\Admin;

use App\Entity\ItemEntity;
use App\Form\ItemEntityType;
use App\Repository\ItemEntityRepository;
use App\Repository\SpellItemRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/item')]
class ItemAdminController extends AbstractController
{
    #[Route('/', name: 'admin_item_index', methods: ['GET'])]
    public function index(Request $request, ItemEntityRepository $itemRepository): Response
    {
        // Get all items for DataTables
        $items = $itemRepository->createQueryBuilder('i')
            ->leftJoin('i.category', 'c')
            ->addSelect('c')
            ->orderBy('c.name', 'ASC')
            ->addOrderBy('i.externalId', 'ASC')
            ->getQuery()
            ->getResult();

        $totalItems = count($items);

        return $this->render('admin/item/index.html.twig', [
            'items' => $items,
            'totalItems' => $totalItems,
        ]);
    }

    #[Route('/new', name: 'admin_item_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $item = new ItemEntity();
        $form = $this->createForm(ItemEntityType::class, $item);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($item);
            $entityManager->flush();

            $this->addFlash('success', 'Item créé avec succès.');

            return $this->redirectToRoute('admin_item_index');
        }

        return $this->render('admin/item/new.html.twig', [
            'item' => $item,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'admin_item_show', methods: ['GET'])]
    public function show(ItemEntity $item, SpellItemRepository $spellItemRepository): Response
    {
        // Get linked spells for this item
        $spellItems = $spellItemRepository->findByItem($item->getId());
        
        return $this->render('admin/item/show.html.twig', [
            'item' => $item,
            'spellItems' => $spellItems,
        ]);
    }

    #[Route('/{id}/edit', name: 'admin_item_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, ItemEntity $item, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(ItemEntityType::class, $item);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $item->setUpdatedAt(new \DateTime());
            $entityManager->flush();

            $this->addFlash('success', 'Item modifié avec succès.');

            return $this->redirectToRoute('admin_item_index');
        }

        return $this->render('admin/item/edit.html.twig', [
            'item' => $item,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'admin_item_delete', methods: ['POST'])]
    public function delete(Request $request, ItemEntity $item, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$item->getId(), $request->request->get('_token'))) {
            $entityManager->remove($item);
            $entityManager->flush();

            $this->addFlash('success', 'Item supprimé avec succès.');
        }

        return $this->redirectToRoute('admin_item_index');
    }
}
