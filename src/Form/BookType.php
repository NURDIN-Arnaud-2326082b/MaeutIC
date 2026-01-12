<?php

/**
 * Formulaire de livre pour la bibliothèque
 *
 * Ce formulaire gère la création et modification de livres :
 * - Titre du livre
 * - Auteur
 * - Lien vers le livre
 * - Lien de l'image de couverture
 * - Sélection de tags pour catégorisation
 */

namespace App\Form;

use App\Entity\Book;
use App\Entity\Tag;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class BookType extends AbstractType
{
    /**
     * Construire le formulaire de livre
     *
     * @param FormBuilderInterface $builder Constructeur de formulaire
     * @param array $options Options du formulaire
     * @return void
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'label' => 'Titre',
                'required' => true,
            ])
            ->add('author', TextType::class, [
                'label' => 'Auteur',
                'required' => true,
            ])
            ->add('link', TextType::class, [
                'label' => "Lien du livre",
                'required' => true,
            ])
            ->add('image', TextType::class, [
                'label' => "Lien de l'image du livre",
                'required' => false,
            ])
            ->add('tags', EntityType::class, [
                'class' => Tag::class,
                'choice_label' => 'name',
                'multiple' => true,
                'expanded' => false,
                'required' => false,
                'mapped' => false,
                'label' => 'Mots-clés (tags)',
                'attr' => [
                    'class' => 'w-full p-2 border border-gray-300 rounded'
                ]
            ]);
    }

    /**
     * Configurer les options du formulaire
     *
     * @param OptionsResolver $resolver Résolveur d'options
     * @return void
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Book::class,
        ]);
    }
}
