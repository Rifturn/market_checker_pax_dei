<?php

namespace App\Controller;

use App\Entity\Avatar;
use App\Entity\AvatarSkill;
use App\Entity\AvatarTeleport;
use App\Form\AvatarType;
use App\Repository\AvatarRepository;
use App\Repository\AvatarTeleportRepository;
use App\Repository\SkillRepository;
use App\Service\PaxDeiClient;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class AvatarController extends AbstractController
{
    #[Route('/avatars', name: 'avatar_index')]
    public function index(AvatarRepository $avatarRepository): Response
    {
        $avatars = $avatarRepository->findAllWithUser();

        return $this->render('avatar/index.html.twig', [
            'avatars' => $avatars,
        ]);
    }

    #[Route('/avatar/new', name: 'avatar_new', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_USER')]
    public function new(Request $request, EntityManagerInterface $entityManager, AvatarRepository $avatarRepository, SkillRepository $skillRepository): Response
    {
        $user = $this->getUser();
        
        // Vérifier si l'utilisateur a déjà un avatar
        $existingAvatar = $avatarRepository->findOneBy(['user' => $user]);
        if ($existingAvatar) {
            $this->addFlash('warning', 'Vous avez déjà un avatar. Vous ne pouvez en créer qu\'un seul par compte.');
            return $this->redirectToRoute('avatar_edit', ['id' => $existingAvatar->getId()]);
        }

        $avatar = new Avatar();
        $avatar->setUser($user);
        
        $form = $this->createForm(AvatarType::class, $avatar, ['hide_user' => true]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Créer automatiquement toutes les compétences à niveau 0
            $skills = $skillRepository->findAll();
            foreach ($skills as $skill) {
                $avatarSkill = new AvatarSkill();
                $avatarSkill->setAvatar($avatar);
                $avatarSkill->setSkill($skill);
                $avatarSkill->setLevel(0);
                $avatar->addAvatarSkill($avatarSkill);
                $entityManager->persist($avatarSkill);
            }
            
            $entityManager->persist($avatar);
            $entityManager->flush();

            $this->addFlash('success', 'Avatar créé avec succès avec toutes les compétences initialisées à 0 !');
            return $this->redirectToRoute('avatar_skills', ['id' => $avatar->getId()]);
        }

        return $this->render('avatar/new.html.twig', [
            'avatar' => $avatar,
            'form' => $form,
        ]);
    }

    #[Route('/avatar/{id}/edit', name: 'avatar_edit', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_USER')]
    public function edit(Request $request, Avatar $avatar, EntityManagerInterface $entityManager): Response
    {
        // Vérifier que l'utilisateur modifie bien son propre avatar
        if ($avatar->getUser() !== $this->getUser()) {
            $this->addFlash('error', 'Vous ne pouvez modifier que votre propre avatar.');
            return $this->redirectToRoute('avatar_index');
        }

        $form = $this->createForm(AvatarType::class, $avatar, ['hide_user' => true]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'Avatar mis à jour avec succès !');
            return $this->redirectToRoute('avatar_show', ['id' => $avatar->getId()]);
        }

        return $this->render('avatar/edit.html.twig', [
            'avatar' => $avatar,
            'form' => $form,
        ]);
    }

    #[Route('/avatar/{id}/skills', name: 'avatar_skills', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_USER')]
    public function editSkills(Request $request, Avatar $avatar, EntityManagerInterface $entityManager, ParameterBagInterface $params): Response
    {
        // Vérifier que l'utilisateur modifie bien son propre avatar
        if ($avatar->getUser() !== $this->getUser()) {
            $this->addFlash('error', 'Vous ne pouvez modifier que votre propre avatar.');
            return $this->redirectToRoute('avatar_index');
        }

        $maxLevel = (int) $params->get('app.skill_max_level');

        if ($request->isMethod('POST')) {
            $skillLevels = $request->request->all('skills');
            
            foreach ($avatar->getAvatarSkills() as $avatarSkill) {
                $skillId = $avatarSkill->getSkill()->getId();
                if (isset($skillLevels[$skillId])) {
                    $level = (int) $skillLevels[$skillId];
                    // Vérifier que le niveau est dans la plage valide
                    if ($level >= 0 && $level <= $maxLevel) {
                        $avatarSkill->setLevel($level);
                    }
                }
            }

            $entityManager->flush();
            $this->addFlash('success', 'Compétences mises à jour avec succès !');
            return $this->redirectToRoute('avatar_show', ['id' => $avatar->getId()]);
        }

        return $this->render('avatar/skills.html.twig', [
            'avatar' => $avatar,
            'maxLevel' => $maxLevel,
        ]);
    }

    #[Route('/avatar/{id}', name: 'avatar_show', methods: ['GET'])]
    public function show(Avatar $avatar): Response
    {
        return $this->render('avatar/show.html.twig', [
            'avatar' => $avatar,
        ]);
    }

    #[Route('/avatar/{id}/teleports', name: 'avatar_teleports', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_USER')]
    public function teleports(Request $request, Avatar $avatar, EntityManagerInterface $entityManager, AvatarTeleportRepository $teleportRepository): Response
    {
        // Vérifier que l'utilisateur modifie bien son propre avatar
        if ($avatar->getUser() !== $this->getUser()) {
            $this->addFlash('error', 'Vous ne pouvez modifier que votre propre avatar.');
            return $this->redirectToRoute('avatar_index');
        }

        // Récupérer tous les maps et zones
        $allMapsWithRegions = PaxDeiClient::getAllMapsWithRegions();
        
        // Récupérer les téléports existants pour cet avatar
        $existingTeleports = $teleportRepository->findByAvatar($avatar->getId());
        
        // Créer un index des téléports existants
        $teleportsIndex = [];
        foreach ($existingTeleports as $tp) {
            $key = $tp->getMap() . '_' . $tp->getZone();
            $teleportsIndex[$key] = $tp;
        }
        
        // Traitement du formulaire
        if ($request->isMethod('POST')) {
            $unlockedTeleports = $request->request->all('teleports') ?? [];
            
            // Parcourir tous les maps et zones
            foreach ($allMapsWithRegions as $map => $zones) {
                foreach ($zones as $zone) {
                    $key = $map . '_' . $zone;
                    $isUnlocked = isset($unlockedTeleports[$key]);
                    
                    // Vérifier si le téléport existe déjà
                    if (isset($teleportsIndex[$key])) {
                        $teleport = $teleportsIndex[$key];
                        $teleport->setUnlocked($isUnlocked);
                    } else {
                        // Créer un nouveau téléport
                        $teleport = new AvatarTeleport();
                        $teleport->setAvatar($avatar);
                        $teleport->setMap($map);
                        $teleport->setZone($zone);
                        $teleport->setUnlocked($isUnlocked);
                        $entityManager->persist($teleport);
                        $teleportsIndex[$key] = $teleport;
                    }
                }
            }
            
            $entityManager->flush();
            $this->addFlash('success', 'Téléports mis à jour avec succès !');
            return $this->redirectToRoute('avatar_teleports', ['id' => $avatar->getId()]);
        }
        
        // Calculer les statistiques
        $stats = $teleportRepository->getCompletionStats($avatar->getId());
        
        // Organiser les données pour l'affichage
        $teleportsData = [];
        foreach ($allMapsWithRegions as $map => $zones) {
            $mapData = [
                'name' => ucfirst($map),
                'zones' => [],
                'unlocked' => 0,
                'total' => count($zones),
            ];
            
            foreach ($zones as $zone) {
                $key = $map . '_' . $zone;
                $isUnlocked = isset($teleportsIndex[$key]) && $teleportsIndex[$key]->isUnlocked();
                
                $mapData['zones'][] = [
                    'name' => ucfirst($zone),
                    'key' => $key,
                    'unlocked' => $isUnlocked,
                ];
                
                if ($isUnlocked) {
                    $mapData['unlocked']++;
                }
            }
            
            $teleportsData[$map] = $mapData;
        }

        return $this->render('avatar/teleports.html.twig', [
            'avatar' => $avatar,
            'teleportsData' => $teleportsData,
            'stats' => $stats,
        ]);
    }

    #[Route('/avatars/teleports/overview', name: 'avatars_teleports_overview')]
    public function teleportsOverview(AvatarRepository $avatarRepository, AvatarTeleportRepository $teleportRepository): Response
    {
        // Récupérer tous les avatars
        $avatars = $avatarRepository->findAllWithUser();
        
        // Récupérer tous les maps et zones
        $allMapsWithRegions = PaxDeiClient::getAllMapsWithRegions();
        
        // Créer un index des téléports par avatar
        $avatarTeleportsIndex = [];
        foreach ($avatars as $avatar) {
            $teleports = $teleportRepository->findByAvatar($avatar->getId());
            $avatarTeleportsIndex[$avatar->getId()] = [];
            
            foreach ($teleports as $tp) {
                if ($tp->isUnlocked()) {
                    $key = $tp->getMap() . '_' . $tp->getZone();
                    $avatarTeleportsIndex[$avatar->getId()][$key] = true;
                }
            }
        }
        
        // Organiser les données par map/zone
        $mapData = [];
        foreach ($allMapsWithRegions as $map => $zones) {
            $mapData[$map] = [
                'name' => ucfirst($map),
                'zones' => [],
            ];
            
            foreach ($zones as $zone) {
                $key = $map . '_' . $zone;
                
                // Trouver quels avatars n'ont PAS ce TP
                $missingAvatars = [];
                $unlockedCount = 0;
                
                foreach ($avatars as $avatar) {
                    if (isset($avatarTeleportsIndex[$avatar->getId()][$key])) {
                        $unlockedCount++;
                    } else {
                        $missingAvatars[] = $avatar;
                    }
                }
                
                $mapData[$map]['zones'][] = [
                    'name' => ucfirst($zone),
                    'key' => $key,
                    'missingAvatars' => $missingAvatars,
                    'unlockedCount' => $unlockedCount,
                    'totalAvatars' => count($avatars),
                ];
            }
        }

        return $this->render('avatar/teleports_overview.html.twig', [
            'mapData' => $mapData,
            'totalAvatars' => count($avatars),
        ]);
    }
}

