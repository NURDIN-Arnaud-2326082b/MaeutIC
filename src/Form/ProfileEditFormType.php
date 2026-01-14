<?php

/**
 * Formulaire d'édition de profil utilisateur
 *
 * Ce formulaire permet aux utilisateurs de modifier leur profil :
 * - Informations de base
 * - Informations académiques
 * - Photo de profil
 * - Réponses aux questions dynamiques
 * - Réponses aux questions taggables
 */

namespace App\Form;

use App\Entity\Tag;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\NotBlank;

class ProfileEditFormType extends AbstractType
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
        $dynamicQuestions = $options['dynamic_questions'] ?? [];
        $taggableQuestions = $options['taggable_questions'] ?? [];
        $tags = $options['tags'] ?? [];

        $builder
            ->add('email', EmailType::class, [
                'constraints' => [
                    new NotBlank(['message' => 'Veuillez entrer un email']),
                ],
            ])
            ->add('username', TextType::class, [
                'required' => false,
            ])
            ->add('lastName', TextType::class, [
                'constraints' => [
                    new NotBlank(['message' => 'Veuillez entrer votre nom']),
                ],
            ])
            ->add('firstName', TextType::class, [
                'constraints' => [
                    new NotBlank(['message' => 'Veuillez entrer votre prénom']),
                ],
            ])
            ->add('affiliationLocation', TextType::class, [
                'required' => false,
            ])
            ->add('specialization', TextType::class, [
                'required' => false,
            ])
            ->add('researchTopic', TextType::class, [
                'required' => false,
            ])
            ->add('userQuestions', CollectionType::class, [
                'entry_type' => TextareaType::class,
                'entry_options' => [
                    'label' => false,
                ],
                'allow_add' => true,
                'mapped' => false,
                'required' => false,

            ])
            ->add('taggableQuestions', CollectionType::class, [
                'entry_type' => EntityType::class,
                'entry_options' => [
                    'class' => Tag::class,
                    'choice_label' => 'name',
                    'label' => false,
                    'multiple' => true,
                    'required' => false,
                    'attr' => ['multiple' => 'multiple'],
                ],
                'mapped' => false,
                'allow_add' => true,
                'by_reference' => false,
            ])
            ->add('profileImageFile', FileType::class, [
                'label' => 'Photo de profil',
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new File([
                        'maxSize' => '2M',
                        'mimeTypes' => [
                            'image/jpeg',
                            'image/png',
                            'image/webp',
                        ],
                        'mimeTypesMessage' => 'Merci d\'uploader une image valide (JPEG, PNG, WEBP)',
                    ])
                ],
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
            'data_class' => User::class,
            'dynamic_questions' => [],
            'taggable_questions' => [],
            'tags' => [],
            'csrf_protection' => true,
            'csrf_field_name' => '_token',
            'csrf_token_id' => 'profile_edit',
        ]);
    }
}
