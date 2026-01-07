<?php

namespace App\Controller\Admin;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/users')]
#[IsGranted('ROLE_ADMIN')]
class UserController extends AbstractController
{
    #[Route('/', name: 'admin_user_index')]
    public function index(UserRepository $userRepository): Response
    {
        $users = $userRepository->findAll();

        return $this->render('admin/user/index.html.twig', [
            'users' => $users,
        ]);
    }

    #[Route('/new', name: 'admin_user_new')]
    public function new(Request $request, EntityManagerInterface $em, UserPasswordHasherInterface $passwordHasher): Response
    {
        if ($request->isMethod('POST')) {
            $username = $request->request->get('username');
            $password = $request->request->get('password');
            $roles = $request->request->all()['roles'] ?? [];

            // Validation basique
            if (empty($username) || empty($password)) {
                $this->addFlash('error', 'Le nom d\'utilisateur et le mot de passe sont obligatoires.');
                return $this->redirectToRoute('admin_user_new');
            }

            // Vérifier si l'utilisateur existe déjà
            $existingUser = $em->getRepository(User::class)->findOneBy(['username' => $username]);
            if ($existingUser) {
                $this->addFlash('error', 'Un utilisateur avec ce nom existe déjà.');
                return $this->redirectToRoute('admin_user_new');
            }

            $user = new User();
            $user->setUsername($username);
            
            // Gérer les rôles
            $userRoles = ['ROLE_USER'];
            if (in_array('ROLE_ADMIN', $roles)) {
                $userRoles[] = 'ROLE_ADMIN';
            }
            $user->setRoles($userRoles);

            // Hash du mot de passe
            $hashedPassword = $passwordHasher->hashPassword($user, $password);
            $user->setPassword($hashedPassword);

            $em->persist($user);
            $em->flush();

            $this->addFlash('success', 'Utilisateur créé avec succès.');
            return $this->redirectToRoute('admin_user_index');
        }

        return $this->render('admin/user/new.html.twig');
    }

    #[Route('/{id}/edit', name: 'admin_user_edit')]
    public function edit(User $user, Request $request, EntityManagerInterface $em, UserPasswordHasherInterface $passwordHasher): Response
    {
        if ($request->isMethod('POST')) {
            $username = $request->request->get('username');
            $password = $request->request->get('password');
            $roles = $request->request->all()['roles'] ?? [];

            if (empty($username)) {
                $this->addFlash('error', 'Le nom d\'utilisateur est obligatoire.');
                return $this->redirectToRoute('admin_user_edit', ['id' => $user->getId()]);
            }

            $user->setUsername($username);

            // Gérer les rôles
            $userRoles = ['ROLE_USER'];
            if (in_array('ROLE_ADMIN', $roles)) {
                $userRoles[] = 'ROLE_ADMIN';
            }
            $user->setRoles($userRoles);

            // Changer le mot de passe uniquement s'il est fourni
            if (!empty($password)) {
                $hashedPassword = $passwordHasher->hashPassword($user, $password);
                $user->setPassword($hashedPassword);
            }

            $em->flush();

            $this->addFlash('success', 'Utilisateur modifié avec succès.');
            return $this->redirectToRoute('admin_user_index');
        }

        return $this->render('admin/user/edit.html.twig', [
            'user' => $user,
        ]);
    }

    #[Route('/{id}/delete', name: 'admin_user_delete', methods: ['POST'])]
    public function delete(User $user, Request $request, EntityManagerInterface $em): Response
    {
        // Empêcher la suppression de son propre compte
        if ($user->getId() === $this->getUser()->getId()) {
            $this->addFlash('error', 'Vous ne pouvez pas supprimer votre propre compte.');
            return $this->redirectToRoute('admin_user_index');
        }

        if ($this->isCsrfTokenValid('delete' . $user->getId(), $request->request->get('_token'))) {
            $em->remove($user);
            $em->flush();

            $this->addFlash('success', 'Utilisateur supprimé avec succès.');
        }

        return $this->redirectToRoute('admin_user_index');
    }
}
