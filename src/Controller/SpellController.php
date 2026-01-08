<?php

namespace App\Controller;

use App\Repository\SpellRepository;
use App\Entity\Spell;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class SpellController extends AbstractController
{
    #[Route('/spells', name: 'spell_index')]
    public function index(SpellRepository $spellRepository): Response
    {
        $spells = $spellRepository->createQueryBuilder('s')
            ->orderBy('s.name', 'ASC')
            ->getQuery()
            ->getResult();

        return $this->render('spell/index.html.twig', [
            'spells' => $spells,
        ]);
    }

    #[Route('/spell/{id}', name: 'spell_show', methods: ['GET'])]
    public function show(Spell $spell): Response
    {
        return $this->render('spell/show.html.twig', [
            'spell' => $spell,
        ]);
    }
}
