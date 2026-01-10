<?php

namespace App\Repository;

use App\Entity\NotificationReaction;
use App\Entity\Notification;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<NotificationReaction>
 */
class NotificationReactionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, NotificationReaction::class);
    }

    /**
     * Find reaction by user, notification and emoji
     */
    public function findByUserNotificationEmoji(User $user, Notification $notification, string $emoji): ?NotificationReaction
    {
        return $this->createQueryBuilder('nr')
            ->where('nr.user = :user')
            ->andWhere('nr.notification = :notification')
            ->andWhere('nr.emoji = :emoji')
            ->setParameter('user', $user)
            ->setParameter('notification', $notification)
            ->setParameter('emoji', $emoji)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
