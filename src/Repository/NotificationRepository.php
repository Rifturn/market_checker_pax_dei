<?php

namespace App\Repository;

use App\Entity\Notification;
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
}
