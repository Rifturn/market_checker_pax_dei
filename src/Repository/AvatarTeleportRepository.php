<?php

namespace App\Repository;

use App\Entity\AvatarTeleport;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AvatarTeleport>
 */
class AvatarTeleportRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AvatarTeleport::class);
    }

    /**
     * Get all teleports for a specific avatar
     */
    public function findByAvatar(int $avatarId): array
    {
        return $this->createQueryBuilder('at')
            ->where('at.avatar = :avatarId')
            ->setParameter('avatarId', $avatarId)
            ->orderBy('at.map', 'ASC')
            ->addOrderBy('at.zone', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Get unlocked teleports for an avatar
     */
    public function findUnlockedByAvatar(int $avatarId): array
    {
        return $this->createQueryBuilder('at')
            ->where('at.avatar = :avatarId')
            ->andWhere('at.unlocked = true')
            ->setParameter('avatarId', $avatarId)
            ->getQuery()
            ->getResult();
    }

    /**
     * Find a specific teleport for avatar, map and zone
     */
    public function findOneByAvatarMapZone(int $avatarId, string $map, string $zone): ?AvatarTeleport
    {
        return $this->createQueryBuilder('at')
            ->where('at.avatar = :avatarId')
            ->andWhere('at.map = :map')
            ->andWhere('at.zone = :zone')
            ->setParameter('avatarId', $avatarId)
            ->setParameter('map', $map)
            ->setParameter('zone', $zone)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Get completion stats by avatar
     */
    public function getCompletionStats(int $avatarId): array
    {
        $result = $this->createQueryBuilder('at')
            ->select('COUNT(at.id) as total', 'SUM(CASE WHEN at.unlocked = true THEN 1 ELSE 0 END) as unlocked')
            ->where('at.avatar = :avatarId')
            ->setParameter('avatarId', $avatarId)
            ->getQuery()
            ->getSingleResult();

        return [
            'total' => (int) ($result['total'] ?? 0),
            'unlocked' => (int) ($result['unlocked'] ?? 0),
        ];
    }
}
