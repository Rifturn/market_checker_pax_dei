<?php

namespace App\Repository;

use App\Entity\NotificationRead;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<NotificationRead>
 */
class NotificationReadRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, NotificationRead::class);
    }

    /**
     * Get the count of unread notifications for a user
     */
    public function countUnreadForUser(User $user): int
    {
        return $this->createQueryBuilder('nr')
            ->select('COUNT(n.id)')
            ->from('App\Entity\Notification', 'n')
            ->leftJoin('n.reads', 'r', 'WITH', 'r.user = :user')
            ->where('r.id IS NULL')
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Check if a notification has been read by a user
     */
    public function hasUserReadNotification(User $user, int $notificationId): bool
    {
        return $this->createQueryBuilder('nr')
            ->select('COUNT(nr.id)')
            ->where('nr.user = :user')
            ->andWhere('nr.notification = :notification')
            ->setParameter('user', $user)
            ->setParameter('notification', $notificationId)
            ->getQuery()
            ->getSingleScalarResult() > 0;
    }
}
