<?php

/**
 * Formulaire de création/édition de tag
 *
 * Ce formulaire simple permet aux administrateurs de créer ou modifier des tags :
 * - Nom du tag (requis)
 *
 * Les tags sont utilisés pour catégoriser auteurs, articles, livres et profils utilisateurs.
 */

namespace App\Form;

use App\Entity\Tag;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TagFormType extends AbstractType
{
    /**
     * Construit le formulaire de tag
     *
     * @param FormBuilderInterface $builder Constructeur de formulaire
     * @param array $options Options du formulaire
     * @return void
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Ajouter un nouveau tag',
                'required' => true,
            ]);
    }

    /**
     * Configure les options du formulaire
     *
     * @param OptionsResolver $resolver Résolveur d'options
     * @return void
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Tag::class,
        ]);
    }
}
