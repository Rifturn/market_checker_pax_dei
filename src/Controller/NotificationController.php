<?php

namespace App\Controller;

use App\Entity\Notification;
use App\Entity\NotificationReaction;
use App\Repository\NotificationRepository;
use App\Repository\NotificationReactionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class NotificationController extends AbstractController
{
    #[Route('/notifications', name: 'notifications_index')]
    public function index(NotificationRepository $notificationRepository): Response
    {
        $notifications = $notificationRepository->findRecentWithDetails(100);

        return $this->render('notification/index.html.twig', [
            'notifications' => $notifications,
        ]);
    }

    #[Route('/notifications/{id}/react', name: 'notification_react', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function react(
        int $id,
        NotificationRepository $notificationRepository,
        NotificationReactionRepository $reactionRepository,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        $notification = $notificationRepository->find($id);
        
        if (!$notification) {
            return new JsonResponse(['error' => 'Notification not found'], 404);
        }

        $user = $this->getUser();
        $emoji = '👏'; // Emoji par défaut

        // Vérifier si l'utilisateur a déjà réagi avec cet emoji
        $existingReaction = $reactionRepository->findByUserNotificationEmoji($user, $notification, $emoji);

        if ($existingReaction) {
            // Retirer la réaction
            $entityManager->remove($existingReaction);
            $entityManager->flush();
            
            $reactionCounts = $notification->getReactionCounts();
            $serializedReactions = $this->serializeReactions($reactionCounts);
            
            return new JsonResponse([
                'success' => true,
                'action' => 'removed',
                'reactions' => $serializedReactions
            ]);
        } else {
            // Ajouter la réaction
            $reaction = new NotificationReaction();
            $reaction->setNotification($notification);
            $reaction->setUser($user);
            $reaction->setEmoji($emoji);
            
            $entityManager->persist($reaction);
            $entityManager->flush();
            
            $reactionCounts = $notification->getReactionCounts();
            $serializedReactions = $this->serializeReactions($reactionCounts);
            
            return new JsonResponse([
                'success' => true,
                'action' => 'added',
                'reactions' => $serializedReactions
            ]);
        }
    }
    
    private function serializeReactions(array $reactionCounts): array
    {
        $serialized = [];
        
        foreach ($reactionCounts as $emoji => $data) {
            $serialized[$emoji] = [
                'count' => $data['count'],
                'users' => array_map(function($user) {
                    // Récupérer le premier avatar de l'utilisateur
                    $avatars = $user->getAvatars();
                    $avatarName = $avatars->count() > 0 ? $avatars->first()->getName() : $user->getUsername();
                    
                    return [
                        'id' => $user->getId(),
                        'username' => $avatarName
                    ];
                }, $data['users'])
            ];
        }
        
        return $serialized;
    }
}
