<?php

namespace App\Controller;

use App\Entity\Notification;
use App\Entity\NotificationRead;
use App\Entity\NotificationReaction;
use App\Repository\NotificationRepository;
use App\Repository\NotificationReactionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class NotificationController extends AbstractController
{
    #[Route('/notifications', name: 'notifications_index')]
    #[IsGranted('ROLE_USER')]
    public function index(NotificationRepository $notificationRepository): Response
    {
        $user = $this->getUser();
        $notifications = $notificationRepository->findRecentWithDetailsForUser($user, 100);
        
        // Comptage manuel des notifications non lues
        $unreadCount = 0;
        foreach ($notifications as $notification) {
            if (!$notification->isReadByUser($user)) {
                $unreadCount++;
            }
        }

        return $this->render('notification/index.html.twig', [
            'notifications' => $notifications,
            'unreadCount' => $unreadCount,
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

    #[Route('/notifications/{id}/mark-read', name: 'notification_mark_read', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function markAsRead(
        int $id,
        NotificationRepository $notificationRepository,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        $notification = $notificationRepository->find($id);
        
        if (!$notification) {
            return new JsonResponse(['error' => 'Notification not found'], 404);
        }

        $user = $this->getUser();
        
        // Vérifier si déjà marquée comme lue
        if (!$notification->isReadByUser($user)) {
            $notificationRead = new NotificationRead();
            $notificationRead->setUser($user);
            $notificationRead->setNotification($notification);
            
            $entityManager->persist($notificationRead);
            $entityManager->flush();
        }

        $unreadCount = $notificationRepository->countUnreadForUser($user);

        return new JsonResponse([
            'success' => true,
            'unreadCount' => $unreadCount
        ]);
    }

    #[Route('/notifications/mark-all-read', name: 'notification_mark_all_read', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function markAllAsRead(
        NotificationRepository $notificationRepository,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        $user = $this->getUser();
        
        // Récupérer toutes les notifications non lues
        $notifications = $notificationRepository->findRecentWithDetailsForUser($user, 1000);
        
        foreach ($notifications as $notification) {
            if (!$notification->isReadByUser($user)) {
                $notificationRead = new NotificationRead();
                $notificationRead->setUser($user);
                $notificationRead->setNotification($notification);
                
                $entityManager->persist($notificationRead);
            }
        }
        
        $entityManager->flush();

        return new JsonResponse([
            'success' => true,
            'unreadCount' => 0
        ]);
    }

    #[Route('/api/notifications/unread-count', name: 'api_notifications_unread_count', methods: ['GET'])]
    public function getUnreadCount(NotificationRepository $notificationRepository): JsonResponse
    {
        if (!$this->getUser()) {
            return new JsonResponse(['count' => 0]);
        }

        $user = $this->getUser();
        
        // Utilisons le même comptage manuel que dans index()
        $notifications = $notificationRepository->findRecentWithDetailsForUser($user, 100);
        $count = 0;
        foreach ($notifications as $notification) {
            if (!$notification->isReadByUser($user)) {
                $count++;
            }
        }
        
        return new JsonResponse(['count' => $count]);
    }
}
