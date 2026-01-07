<?php

namespace App\Repository;

use App\Entity\ItemEntity;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ItemEntity>
 */
class ItemEntityRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ItemEntity::class);
    }

    public function findByExternalId(string $externalId): ?ItemEntity
    {
        return $this->findOneBy(['externalId' => $externalId]);
    }

    public function findAllWithCategory(): array
    {
        return $this->createQueryBuilder('i')
            ->leftJoin('i.category', 'c')
            ->addSelect('c')
            ->orderBy('c.name', 'ASC')
            ->addOrderBy('i.externalId', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
