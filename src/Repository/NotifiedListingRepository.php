<?php

namespace App\Repository;

use App\Entity\NotifiedListing;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<NotifiedListing>
 */
class NotifiedListingRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, NotifiedListing::class);
    }

    public function isNotified(string $listingId): bool
    {
        return $this->count(['listingId' => $listingId]) > 0;
    }

    public function cleanOldNotifications(int $daysToKeep = 7): int
    {
        $date = new \DateTimeImmutable(sprintf('-%d days', $daysToKeep));
        
        return $this->createQueryBuilder('n')
            ->delete()
            ->where('n.notifiedAt < :date')
            ->setParameter('date', $date)
            ->getQuery()
            ->execute();
    }
}
