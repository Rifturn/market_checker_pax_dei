<?php

namespace App\Repository;

use App\Entity\GuildStock;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<GuildStock>
 */
class GuildStockRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, GuildStock::class);
    }

    //    /**
    //     * @return GuildStock[] Returns an array of GuildStock objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('g')
    //            ->andWhere('g.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('g.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    /**
     * Retourne les IDs des items présents dans le stock de guilde
     * @return array<int>
     */
    public function getStockedItemIds(): array
    {
        $result = $this->createQueryBuilder('g')
            ->select('IDENTITY(g.item) as item_id')
            ->getQuery()
            ->getResult();
        
        return array_map(fn($row) => $row['item_id'], $result);
    }

    //    public function findOneBySomeField($value): ?GuildStock
    //    {
    //        return $this->createQueryBuilder('g')
    //            ->andWhere('g.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
