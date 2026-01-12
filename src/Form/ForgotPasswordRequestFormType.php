<?php

/**
 * Formulaire de demande de réinitialisation de mot de passe
 *
 * Ce formulaire permet aux utilisateurs de demander un lien de réinitialisation :
 * - Saisie de l'adresse email
 * - Validation de format email
 * - Champ requis
 */

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\NotBlank;

class ForgotPasswordRequestFormType extends AbstractType
{
    /**
     * Construire le formulaire de demande de réinitialisation de mot de passe
     *
     * @param FormBuilderInterface $builder Constructeur de formulaire
     * @param array $options Options du formulaire
     * @return void
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('email', EmailType::class, [
                'label' => 'Votre adresse email',
                'attr' => [
                    'autocomplete' => 'email',
                    'class' => 'mt-1 block w-full border border-gray-300 rounded-md p-2 focus:ring-blue-500 focus:border-blue-500',
                    'placeholder' => 'contact@exemple.fr'
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Veuillez entrer votre adresse email.',
                    ]),
                    new Email([
                        'message' => 'L\'adresse email n\'est pas valide.',
                    ]),
                ],
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
        $resolver->setDefaults([]);
    }
}