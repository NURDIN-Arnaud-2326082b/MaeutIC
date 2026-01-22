<?php

/**
 * Formulaire d'inscription utilisateur
 *
 * Ce formulaire gère l'inscription des nouveaux utilisateurs avec :
 * - Informations de base (email, username, password, nom, prénom)
 * - Informations académiques (affiliation, spécialisation, thématique)
 * - Photo de profil
 * - Questions dynamiques personnalisées (minimum requis)
 * - Questions taggables avec sélection de tags
 * - Case d'acceptation des CGU
 * - Validation côté serveur
 */

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\IsTrue;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class RegistrationFormType extends AbstractType
{
    /**
     * Construction du formulaire d'inscription
     *
     * @param FormBuilderInterface $builder Constructeur de formulaire
     * @param array $options Options du formulaire
     * @return void
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $dynamicQuestions = $options['dynamic_questions'] ?? [];
        $minQuestionsRequired = $options['min_questions_required'] ?? 0;
        $taggableQuestions = $options['taggable_questions'] ?? [];
        $tags = $options['tags'] ?? [];
        $taggableMinChoices = $options['taggable_min_choices'] ?? [];

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
            ->add('genre', ChoiceType::class, [
                'label' => 'Genre',
                'required' => true,
                'choices' => [
                    'Homme' => 'male',
                    'Femme' => 'female',
                    'Autre' => 'other',
                    'Je préfère ne pas répondre' => 'prefer_not_to_say',
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Veuillez sélectionner votre genre',
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
            ->add("taggableQuestions", CollectionType::class, [
                'entry_type' => ChoiceType::class,
                'entry_options' => [
                    'choices' => $tags,
                    'choice_label' => function ($tag) {
                        return $tag->getName();
                    },
                    'choice_value' => function ($tag) {
                        return $tag ? $tag->getId() : '';
                    },
                    'label' => false,
                    'multiple' => true,
                    'attr' => [
                        'multiple' => 'multiple',
                    ],
                ],
                'mapped' => false,
                'allow_add' => true,
                'required' => false,
                'data' => array_fill(0, count($taggableQuestions), []),
                'constraints' => [
                    new Callback([
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
            ])->add("taggableQuestions", CollectionType::class, [
                'entry_type' => ChoiceType::class,
                'entry_options' => [
                    'choices' => $tags,
                    'choice_label' => function ($tag) {
                        return $tag->getName();
                    },
                    'choice_value' => function ($tag) {
                        return $tag ? $tag->getId() : '';
                    },
                    'label' => false,
                    'multiple' => true,
                    'attr' => [
                        'multiple' => 'multiple',
                    ],
                ],
                'mapped' => false,
                'allow_add' => true,
                'required' => false,
                'data' => array_fill(0, count($taggableQuestions), []),
                'constraints' => [
                    new Callback([
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
            ])->add("taggableQuestions", CollectionType::class, [
                'entry_type' => ChoiceType::class,
                'entry_options' => [
                    'choices' => $tags,
                    'choice_label' => function ($tag) {
                        return $tag->getName();
                    },
                    'choice_value' => function ($tag) {
                        return $tag ? $tag->getId() : '';
                    },
                    'label' => false,
                    'multiple' => true,
                    'attr' => [
                        'multiple' => 'multiple',
                    ],
                ],
                'mapped' => false,
                'allow_add' => true,
                'required' => false,
                'data' => array_fill(0, count($taggableQuestions), []),
                'constraints' => [
                    new Callback([
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
            ])->add("taggableQuestions", CollectionType::class, [
                'entry_type' => ChoiceType::class,
                'entry_options' => [
                    'choices' => $tags,
                    'choice_label' => function ($tag) {
                        return $tag->getName();
                    },
                    'choice_value' => function ($tag) {
                        return $tag ? $tag->getId() : '';
                    },
                    'label' => false,
                    'multiple' => true,
                    'attr' => [
                        'multiple' => 'multiple',
                    ],
                ],
                'mapped' => false,
                'allow_add' => true,
                'required' => false,
                'data' => array_fill(0, count($taggableQuestions), []),
                'constraints' => [
                    new Callback([
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
            ])->add("taggableQuestions", CollectionType::class, [
                'entry_type' => ChoiceType::class,
                'entry_options' => [
                    'choices' => $tags,
                    'choice_label' => function ($tag) {
                        return $tag->getName();
                    },
                    'choice_value' => function ($tag) {
                        return $tag ? $tag->getId() : '';
                    },
                    'label' => false,
                    'multiple' => true,
                    'attr' => [
                        'multiple' => 'multiple',
                    ],
                ],
                'mapped' => false,
                'allow_add' => true,
                'required' => false,
                'data' => array_fill(0, count($taggableQuestions), []),
                'constraints' => [
                    new Callback([
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
            ])->add("taggableQuestions", CollectionType::class, [
                'entry_type' => ChoiceType::class,
                'entry_options' => [
                    'choices' => $tags,
                    'choice_label' => function ($tag) {
                        return $tag->getName();
                    },
                    'choice_value' => function ($tag) {
                        return $tag ? $tag->getId() : '';
                    },
                    'label' => false,
                    'multiple' => true,
                    'attr' => [
                        'multiple' => 'multiple',
                    ],
                ],
                'mapped' => false,
                'allow_add' => true,
                'required' => false,
                'data' => array_fill(0, count($taggableQuestions), []),
                'constraints' => [
                    new Callback([
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
            ])->add("taggableQuestions", CollectionType::class, [
                'entry_type' => ChoiceType::class,
                'entry_options' => [
                    'choices' => $tags,
                    'choice_label' => function ($tag) {
                        return $tag->getName();
                    },
                    'choice_value' => function ($tag) {
                        return $tag ? $tag->getId() : '';
                    },
                    'label' => false,
                    'multiple' => true,
                    'attr' => [
                        'multiple' => 'multiple',
                    ],
                ],
                'mapped' => false,
                'allow_add' => true,
                'required' => false,
                'data' => array_fill(0, count($taggableQuestions), []),
                'constraints' => [
                    new Callback([
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
            ])->add("taggableQuestions", CollectionType::class, [
                'entry_type' => ChoiceType::class,
                'entry_options' => [
                    'choices' => $tags,
                    'choice_label' => function ($tag) {
                        return $tag->getName();
                    },
                    'choice_value' => function ($tag) {
                        return $tag ? $tag->getId() : '';
                    },
                    'label' => false,
                    'multiple' => true,
                    'attr' => [
                        'multiple' => 'multiple',
                    ],
                ],
                'mapped' => false,
                'allow_add' => true,
                'required' => false,
                'data' => array_fill(0, count($taggableQuestions), []),
                'constraints' => [
                    new Callback([
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
            ])->add("taggableQuestions", CollectionType::class, [
                'entry_type' => ChoiceType::class,
                'entry_options' => [
                    'choices' => $tags,
                    'choice_label' => function ($tag) {
                        return $tag->getName();
                    },
                    'choice_value' => function ($tag) {
                        return $tag ? $tag->getId() : '';
                    },
                    'label' => false,
                    'multiple' => true,
                    'attr' => [
                        'multiple' => 'multiple',
                    ],
                ],
                'mapped' => false,
                'allow_add' => true,
                'required' => false,
                'data' => array_fill(0, count($taggableQuestions), []),
                'constraints' => [
                    new Callback([
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
            ])->add("taggableQuestions", CollectionType::class, [
                'entry_type' => ChoiceType::class,
                'entry_options' => [
                    'choices' => $tags,
                    'choice_label' => function ($tag) {
                        return $tag->getName();
                    },
                    'choice_value' => function ($tag) {
                        return $tag ? $tag->getId() : '';
                    },
                    'label' => false,
                    'multiple' => true,
                    'attr' => [
                        'multiple' => 'multiple',
                    ],
                ],
                'mapped' => false,
                'allow_add' => true,
                'required' => false,
                'data' => array_fill(0, count($taggableQuestions), []),
                'constraints' => [
                    new Callback([
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
            ])->add("taggableQuestions", CollectionType::class, [
                'entry_type' => ChoiceType::class,
                'entry_options' => [
                    'choices' => $tags,
                    'choice_label' => function ($tag) {
                        return $tag->getName();
                    },
                    'choice_value' => function ($tag) {
                        return $tag ? $tag->getId() : '';
                    },
                    'label' => false,
                    'multiple' => true,
                    'attr' => [
                        'multiple' => 'multiple',
                    ],
                ],
                'mapped' => false,
                'allow_add' => true,
                'required' => false,
                'data' => array_fill(0, count($taggableQuestions), []),
                'constraints' => [
                    new Callback([
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
            ])->add("taggableQuestions", CollectionType::class, [
                'entry_type' => ChoiceType::class,
                'entry_options' => [
                    'choices' => $tags,
                    'choice_label' => function ($tag) {
                        return $tag->getName();
                    },
                    'choice_value' => function ($tag) {
                        return $tag ? $tag->getId() : '';
                    },
                    'label' => false,
                    'multiple' => true,
                    'attr' => [
                        'multiple' => 'multiple',
                    ],
                ],
                'mapped' => false,
                'allow_add' => true,
                'required' => false,
                'data' => array_fill(0, count($taggableQuestions), []),
                'constraints' => [
                    new Callback([
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
            ])->add("taggableQuestions", CollectionType::class, [
                'entry_type' => ChoiceType::class,
                'entry_options' => [
                    'choices' => $tags,
                    'choice_label' => function ($tag) {
                        return $tag->getName();
                    },
                    'choice_value' => function ($tag) {
                        return $tag ? $tag->getId() : '';
                    },
                    'label' => false,
                    'multiple' => true,
                    'attr' => [
                        'multiple' => 'multiple',
                    ],
                ],
                'mapped' => false,
                'allow_add' => true,
                'required' => false,
                'data' => array_fill(0, count($taggableQuestions), []),
                'constraints' => [
                    new Callback([
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
            ])->add("taggableQuestions", CollectionType::class, [
                'entry_type' => ChoiceType::class,
                'entry_options' => [
                    'choices' => $tags,
                    'choice_label' => function ($tag) {
                        return $tag->getName();
                    },
                    'choice_value' => function ($tag) {
                        return $tag ? $tag->getId() : '';
                    },
                    'label' => false,
                    'multiple' => true,
                    'attr' => [
                        'multiple' => 'multiple',
                    ],
                ],
                'mapped' => false,
                'allow_add' => true,
                'required' => false,
                'data' => array_fill(0, count($taggableQuestions), []),
                'constraints' => [
                    new Callback([
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
            ])->add("taggableQuestions", CollectionType::class, [
                'entry_type' => ChoiceType::class,
                'entry_options' => [
                    'choices' => $tags,
                    'choice_label' => function ($tag) {
                        return $tag->getName();
                    },
                    'choice_value' => function ($tag) {
                        return $tag ? $tag->getId() : '';
                    },
                    'label' => false,
                    'multiple' => true,
                    'attr' => [
                        'multiple' => 'multiple',
                    ],
                ],
                'mapped' => false,
                'allow_add' => true,
                'required' => false,
                'data' => array_fill(0, count($taggableQuestions), []),
                'constraints' => [
                    new Callback([
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
            ])->add("taggableQuestions", CollectionType::class, [
                'entry_type' => ChoiceType::class,
                'entry_options' => [
                    'choices' => $tags,
                    'choice_label' => function ($tag) {
                        return $tag->getName();
                    },
                    'choice_value' => function ($tag) {
                        return $tag ? $tag->getId() : '';
                    },
                    'label' => false,
                    'multiple' => true,
                    'attr' => [
                        'multiple' => 'multiple',
                    ],
                ],
                'mapped' => false,
                'allow_add' => true,
                'required' => false,
                'data' => array_fill(0, count($taggableQuestions), []),
                'constraints' => [
                    new Callback([
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

            ->add("taggableQuestions", CollectionType::class, [
                'entry_type' => ChoiceType::class,
                'entry_options' => [
                    'choices' => $tags,
                    'choice_label' => function ($tag) {
                        return $tag->getName();
                    },
                    'choice_value' => function ($tag) {
                        return $tag ? $tag->getId() : '';
                    },
                    'label' => false,
                    'multiple' => true,
                    'attr' => [
                        'multiple' => 'multiple',
                    ],
                ],
                'mapped' => false,
                'allow_add' => true,
                'required' => false,
                'data' => array_fill(0, count($taggableQuestions), []),
                'constraints' => [
                    new Callback([
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

            ->add('userQuestions', CollectionType::class, [
                'entry_type' => TextareaType::class,
                'entry_options' => [
                    'label' => false,
                ],
                'allow_add' => true,
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new Callback([
                        'callback' => function ($questions, $context) use ($minQuestionsRequired) {
                            $answeredQuestions = 0;
                            foreach ($questions as $questionText) {
                                if (!empty(trim($questionText))) {
                                    $answeredQuestions++;
                                }
                            }

                            if ($answeredQuestions < $minQuestionsRequired) {
                                $context->buildViolation("Vous devez répondre à au moins {$minQuestionsRequired} questions.")
                                    ->atPath('userQuestions')
                                    ->addViolation();
                            }
                        },
                    ]),
                ],
                'data' => array_fill(0, count($dynamicQuestions), ''),
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
            'min_questions_required' => 3, // Valeur par défaut
            'taggable_questions' => [],
            'tags' => [],
            'taggable_min_choices' => [],
        ]);
    }
}