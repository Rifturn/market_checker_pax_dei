<?php

namespace App\Form;

use App\Entity\Spell;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SpellType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('externalId', TextType::class, [
                'label' => 'ID Externe',
                'attr' => ['class' => 'form-control']
            ])
            ->add('name', TextType::class, [
                'label' => 'Nom',
                'attr' => ['class' => 'form-control']
            ])
            ->add('iconPath', TextType::class, [
                'label' => 'Chemin icône',
                'required' => false,
                'attr' => ['class' => 'form-control']
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => false,
                'attr' => ['class' => 'form-control', 'rows' => 5]
            ])
            ->add('cooldownDuration', NumberType::class, [
                'label' => 'Durée de cooldown',
                'required' => false,
                'attr' => ['class' => 'form-control', 'step' => '0.1']
            ])
            ->add('range', NumberType::class, [
                'label' => 'Portée',
                'required' => false,
                'attr' => ['class' => 'form-control', 'step' => '0.1']
            ])
            ->add('costAttribute', TextType::class, [
                'label' => 'Type de coût',
                'required' => false,
                'attr' => ['class' => 'form-control']
            ])
            ->add('costAmountMin', NumberType::class, [
                'label' => 'Coût minimum',
                'required' => false,
                'attr' => ['class' => 'form-control', 'step' => '0.1']
            ])
            ->add('entityType', TextType::class, [
                'label' => 'Type d\'entité',
                'attr' => ['class' => 'form-control']
            ])
            ->add('entityTypeName', TextType::class, [
                'label' => 'Nom du type d\'entité',
                'attr' => ['class' => 'form-control']
            ])
            ->add('listingPath', TextType::class, [
                'label' => 'Chemin de listing',
                'attr' => ['class' => 'form-control']
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Spell::class,
        ]);
    }
}
