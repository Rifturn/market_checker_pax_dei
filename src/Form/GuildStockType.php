<?php

namespace App\Form;

use App\Entity\GuildStock;
use App\Entity\ItemEntity;
use App\Repository\ItemEntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class GuildStockType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $existingStock = $options['existing_stock'];
        $currentStock = $builder->getData();
        
        // Récupérer les IDs des items déjà en stock (sauf l'item actuel en cas d'édition)
        $excludedItemIds = [];
        foreach ($existingStock as $stock) {
            // Si on édite, ne pas exclure l'item actuel
            if (!$currentStock || $stock->getId() !== $currentStock->getId()) {
                $excludedItemIds[] = $stock->getItem()->getId();
            }
        }
        
        $builder
            ->add('item', EntityType::class, [
                'class' => ItemEntity::class,
                'choice_label' => function(ItemEntity $item) {
                    return $item->getName()['Fr'] ?? $item->getName()['En'] ?? $item->getExternalId();
                },
                'query_builder' => function(ItemEntityRepository $repo) use ($excludedItemIds) {
                    $qb = $repo->createQueryBuilder('i')
                        ->join('i.category', 'c')
                        ->where('c.name = :reliques')
                        ->setParameter('reliques', 'Reliques');
                    
                    if (!empty($excludedItemIds)) {
                        $qb->andWhere('i.id NOT IN (:excludedIds)')
                           ->setParameter('excludedIds', $excludedItemIds);
                    }
                    
                    return $qb->orderBy('i.name', 'ASC');
                },
                'label' => 'Relique',
                'attr' => ['class' => 'item-select'],
                'placeholder' => 'Sélectionnez une relique...'
            ])
            ->add('quantity', IntegerType::class, [
                'label' => 'Quantité',
                'attr' => [
                    'class' => 'form-control',
                    'min' => 0,
                    'placeholder' => 'Ex: 5'
                ]
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => GuildStock::class,
            'existing_stock' => [],
        ]);
    }
}
