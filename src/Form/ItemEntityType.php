<?php

namespace App\Form;

use App\Entity\Category;
use App\Entity\ItemEntity;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\CallbackTransformer;

class ItemEntityType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('externalId', TextType::class, [
                'label' => 'ID Externe (API)',
                'attr' => ['class' => 'form-control']
            ])
            ->add('name', TextareaType::class, [
                'label' => 'Noms (JSON)',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 5,
                    'placeholder' => '{"Fr": "Nom français", "En": "English name"}'
                ],
                'help' => 'Format JSON avec les clés: De, En, Es, Fr, Pl'
            ])
            ->add('iconPath', TextType::class, [
                'label' => 'Chemin de l\'icône',
                'required' => false,
                'attr' => ['class' => 'form-control']
            ])
            ->add('url', TextType::class, [
                'label' => 'URL',
                'required' => false,
                'attr' => ['class' => 'form-control']
            ])
            ->add('category', EntityType::class, [
                'class' => Category::class,
                'choice_label' => 'name',
                'label' => 'Catégorie',
                'placeholder' => '-- Sélectionner une catégorie --',
                'required' => false,
                'attr' => ['class' => 'form-select']
            ])
        ;

        // Transformer pour le champ name (array <-> JSON string)
        $builder->get('name')->addModelTransformer(new CallbackTransformer(
            function ($nameAsArray) {
                // Transform array to JSON string for display
                if (is_array($nameAsArray)) {
                    return json_encode($nameAsArray, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
                }
                return $nameAsArray;
            },
            function ($nameAsString) {
                // Transform JSON string back to array for storage
                if (is_string($nameAsString) && !empty($nameAsString)) {
                    $decoded = json_decode($nameAsString, true);
                    if (json_last_error() === JSON_ERROR_NONE) {
                        return $decoded;
                    }
                }
                return $nameAsString;
            }
        ));
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ItemEntity::class,
        ]);
    }
}
