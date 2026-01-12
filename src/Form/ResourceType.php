<?php

/**
 * Formulaire de ressource
 *
 * Ce formulaire gère les ressources (liens) affichées sur les pages thématiques :
 * - Titre de la ressource
 * - Description
 * - Lien (YouTube ou autre URL)
 *
 * Utilisé pour les pages : Chill, Methodology, Administrative
 */

namespace App\Form;

use App\Entity\Resource;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ResourceType extends AbstractType
{
    /**
     * Construction du formulaire
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
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => true,
            ])
            ->add('link', TextType::class, [
                'label' => 'Lien (YouTube ou autre)',
                'required' => false,
            ]);
    }

    /**
     * Configuration des options du formulaire
     *
     * @param OptionsResolver $resolver Résolveur d'options
     * @return void
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Resource::class,
        ]);
    }
}
