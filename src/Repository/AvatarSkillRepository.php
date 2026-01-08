<?php

namespace App\Repository;

use App\Entity\AvatarSkill;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AvatarSkill>
 */
class AvatarSkillRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AvatarSkill::class);
    }

    public function findByAvatar(int $avatarId): array
    {
        return $this->createQueryBuilder('as')
            ->leftJoin('as.skill', 's')
            ->addSelect('s')
            ->andWhere('as.avatar = :avatarId')
            ->setParameter('avatarId', $avatarId)
            ->orderBy('s.name', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
