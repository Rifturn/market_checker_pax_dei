<?php

namespace App\Form;

use App\Entity\Category;
use App\Entity\ItemEntity;
use App\Entity\UserView;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UserViewType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Nom de la vue',
                'attr' => ['class' => 'form-control', 'placeholder' => 'Ex: Mes objets de craft']
            ])
            ->add('categories', EntityType::class, [
                'class' => Category::class,
                'choice_label' => 'name',
                'multiple' => true,
                'expanded' => false,
                'label' => 'Catégories',
                'required' => false,
                'attr' => ['class' => 'category-select']
            ])
            ->add('items', EntityType::class, [
                'class' => ItemEntity::class,
                'choice_label' => function(ItemEntity $item) {
                    return $item->getName()['Fr'] ?? $item->getName()['En'] ?? $item->getExternalId();
                },
                'group_by' => function(ItemEntity $item) {
                    return $item->getCategory() ? $item->getCategory()->getName() : 'Sans catégorie';
                },
                'multiple' => true,
                'expanded' => false,
                'label' => 'Items spécifiques',
                'required' => false,
                'attr' => ['class' => 'item-select']
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => UserView::class,
        ]);
    }
}
