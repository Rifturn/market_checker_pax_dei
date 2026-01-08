<?php

namespace App\Repository;

use App\Entity\Skill;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Skill>
 */
class SkillRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Skill::class);
    }

    public function findByExternalId(string $externalId): ?Skill
    {
        return $this->findOneBy(['externalId' => $externalId]);
    }

    /**
     * @return Skill[]
     */
    public function findAll(): array
    {
        return $this->findBy([], ['name' => 'ASC']);
    }
}
