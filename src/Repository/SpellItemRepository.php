<?php

namespace App\Repository;

use App\Entity\SpellItem;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<SpellItem>
 */
class SpellItemRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SpellItem::class);
    }

    public function findBySpell(int $spellId): array
    {
        return $this->createQueryBuilder('si')
            ->leftJoin('si.item', 'i')
            ->addSelect('i')
            ->andWhere('si.spell = :spellId')
            ->setParameter('spellId', $spellId)
            ->getQuery()
            ->getResult();
    }

    public function findByItem(int $itemId): array
    {
        return $this->createQueryBuilder('si')
            ->leftJoin('si.spell', 's')
            ->addSelect('s')
            ->andWhere('si.item = :itemId')
            ->setParameter('itemId', $itemId)
            ->getQuery()
            ->getResult();
    }
}
