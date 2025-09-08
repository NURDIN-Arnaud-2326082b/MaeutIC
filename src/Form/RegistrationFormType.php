<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\IsTrue;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
class RegistrationFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $dynamicQuestions = $options['dynamic_questions'] ?? [];
        $requiredQuestions = $options['required_questions'] ?? [];
        $taggableQuestions = $options['taggable_questions'] ?? [];
        $tags = $options['tags'] ?? []; // Liste des tags à afficher dans les menus déroulants
        $taggableMinChoices = $options['taggable_min_choices'] ?? []; // Tableau d'entiers (min par question)

        $builder
            ->add('email', EmailType::class, [
                'constraints' => [
                    new NotBlank([
                        'message' => 'Please enter an email',
                    ]),
                ],
            ])
            ->add('username', TextType::class, [
                'required' => false,
            ])
            ->add('plainPassword', PasswordType::class, [
                'mapped' => false,
                'attr' => ['autocomplete' => 'new-password'],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Please enter a password',
                    ]),
                    new Length([
                        'min' => 6,
                        'minMessage' => 'Your password should be at least {{ limit }} characters',
                        'max' => 4096,
                    ]),
                ],
            ])
            ->add('lastName', TextType::class, [
                'constraints' => [
                    new NotBlank([
                        'message' => 'Please enter your last name',
                    ]),
                ],
            ])
            ->add('firstName', TextType::class, [
                'constraints' => [
                    new NotBlank([
                        'message' => 'Please enter your first name',
                    ]),
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
            ->add('agreeTerms', CheckboxType::class, [
                'mapped' => false,
                'constraints' => [
                    new IsTrue([
                        'message' => 'You should agree to our terms.',
                    ]),
                ],
            ])
            ->add('userQuestions', CollectionType::class, [
                'entry_type' => TextareaType::class,
                'entry_options' => [
                    'label' => false,
                ],
                'allow_add' => true,
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new \Symfony\Component\Validator\Constraints\Callback([
                        'callback' => function ($questions, $context) use ($requiredQuestions) {
                            foreach ($requiredQuestions as $index) {
                                if (empty($questions[$index])) {
                                    $context->buildViolation('Cette question est obligatoire.')
                                        ->atPath("[$index]")
                                        ->addViolation();
                                }
                            }
                        },
                    ]),
                ],
                'data' => array_fill(0, count($dynamicQuestions), ''), // Pré-remplir avec des champs vides
            ])
            ->add("taggableQuestions", CollectionType::class, [
                'entry_type' => ChoiceType::class,
                'entry_options' => [
                    'choices' => $tags,
                    'choice_label' => function($tag) {
                        return $tag->getName();
                    },
                    'choice_value' => function($tag) {
                        return $tag ? $tag->getId() : '';
                    },
                    'label' => false,
                    'multiple' => true, // Permet la sélection multiple
                    'attr' => [
                        'multiple' => 'multiple', // Pour le rendu HTML (utile si JS désactivé)
                    ],
                ],
                'mapped' => false,
                'allow_add' => true,
                'required' => false,
                'data' => array_fill(0, count($taggableQuestions), []), // Tableau vide pour chaque question
                'constraints' => [
                    new \Symfony\Component\Validator\Constraints\Callback([
                        'callback' => function ($taggable, $context) use ($taggableMinChoices) {
                            foreach ($taggableMinChoices as $index => $min) {
                                if (
                                    isset($taggable[$index]) &&
                                    is_array($taggable[$index]) &&
                                    count(array_filter($taggable[$index])) < $min
                                ) {
                                    $context->buildViolation("Veuillez sélectionner au moins $min tag(s) pour cette question.")
                                        ->atPath("[$index]")
                                        ->addViolation();
                                }
                            }
                        }
                    ])
                ],
            ])
            ->add('profileImageFile', FileType::class, [
                'label' => 'Photo de profil',
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new \Symfony\Component\Validator\Constraints\File([
                        'maxSize' => '2M',
                        'mimeTypes' => [
                            'image/jpeg',
                            'image/png',
                            'image/webp',
                        ],
                        'mimeTypesMessage' => 'Merci d\'uploader une image valide (JPEG, PNG, WEBP)',
                    ])
                ],
            ])
                    
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'dynamic_questions' => [],
            'required_questions' => [],
            'taggable_questions' => [],
            'tags' => [], // Nouvelle option pour passer la liste des tags
            'taggable_min_choices' => [], // Tableau d'entiers, min par question taggable
        ]);
    }
}