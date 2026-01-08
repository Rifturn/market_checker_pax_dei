<?php

namespace App\Controller\Admin;

use App\Entity\Spell;
use App\Form\SpellType;
use App\Repository\SpellRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/spell')]
class SpellAdminController extends AbstractController
{
    #[Route('/', name: 'admin_spell_index', methods: ['GET'])]
    public function index(SpellRepository $spellRepository): Response
    {
        return $this->render('admin/spell/index.html.twig', [
            'spells' => $spellRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'admin_spell_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $spell = new Spell();
        $form = $this->createForm(SpellType::class, $spell);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($spell);
            $entityManager->flush();

            $this->addFlash('success', 'Sort créé avec succès.');

            return $this->redirectToRoute('admin_spell_index');
        }

        return $this->render('admin/spell/new.html.twig', [
            'spell' => $spell,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'admin_spell_show', methods: ['GET'])]
    public function show(Spell $spell): Response
    {
        return $this->render('admin/spell/show.html.twig', [
            'spell' => $spell,
        ]);
    }

    #[Route('/{id}/edit', name: 'admin_spell_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Spell $spell, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(SpellType::class, $spell);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'Sort modifié avec succès.');

            return $this->redirectToRoute('admin_spell_index');
        }

        return $this->render('admin/spell/edit.html.twig', [
            'spell' => $spell,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'admin_spell_delete', methods: ['POST'])]
    public function delete(Request $request, Spell $spell, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$spell->getId(), $request->request->get('_token'))) {
            $entityManager->remove($spell);
            $entityManager->flush();

            $this->addFlash('success', 'Sort supprimé avec succès.');
        }

        return $this->redirectToRoute('admin_spell_index');
    }
}
