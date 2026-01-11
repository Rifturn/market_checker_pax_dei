<?php

namespace App\Repository;

use App\Entity\Notification;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Notification>
 */
class NotificationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Notification::class);
    }

    /**
     * Get recent notifications with avatar and reactions
     */
    public function findRecentWithDetails(int $limit = 50): array
    {
        return $this->createQueryBuilder('n')
            ->leftJoin('n.avatar', 'a')
            ->leftJoin('n.reactions', 'r')
            ->addSelect('a', 'r')
            ->orderBy('n.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Get count of unread notifications for a user
     */
    public function countUnreadForUser(User $user): int
    {
        $em = $this->getEntityManager();
        
        $query = $em->createQuery('
            SELECT COUNT(n.id)
            FROM App\Entity\Notification n
            WHERE NOT EXISTS (
                SELECT 1 
                FROM App\Entity\NotificationRead nr 
                WHERE nr.notification = n AND nr.user = :user
            )
        ');
        
        $query->setParameter('user', $user);
        
        return $query->getSingleScalarResult();
    }

    /**
     * Get recent notifications with read status for a user
     */
    public function findRecentWithDetailsForUser(User $user, int $limit = 50): array
    {
        return $this->createQueryBuilder('n')
            ->leftJoin('n.avatar', 'a')
            ->leftJoin('n.reactions', 'r')
            ->leftJoin('n.reads', 'nr', 'WITH', 'nr.user = :user')
            ->addSelect('a', 'r', 'nr')
            ->orderBy('n.createdAt', 'DESC')
            ->setParameter('user', $user)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
