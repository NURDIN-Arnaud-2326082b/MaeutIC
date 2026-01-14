<?php

/**
 * Contrôleur d'inscription des utilisateurs
 *
 * Ce contrôleur gère le processus complet d'inscription des nouveaux utilisateurs
 * incluant la création du compte, la validation reCAPTCHA, l'upload de photo de profil,
 * et la gestion des questions dynamiques et taggables du formulaire d'inscription
 */

namespace App\Controller;

use App\Entity\User;
use App\Entity\UserQuestions;
use App\Form\RegistrationFormType;
use App\Repository\TagRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class RegistrationController extends AbstractController
{
    /**
     * Gère l'inscription d'un nouvel utilisateur
     *
     * Cette méthode affiche et traite le formulaire d'inscription avec :
     * - Questions dynamiques
     * - Questions taggables
     * - Validation reCAPTCHA
     * - Upload de photo de profil
     * - Hachage du mot de passe
     * - Connexion automatique après inscription
     *
     * @param Request $request La requête HTTP contenant les données du formulaire
     * @param UserPasswordHasherInterface $userPasswordHasher Service pour hasher les mots de passe
     * @param Security $security Service de sécurité pour connecter l'utilisateur
     * @param EntityManagerInterface $entityManager Gestionnaire d'entités Doctrine
     * @param TagRepository $tagRepository Repository des tags
     * @param SluggerInterface $slugger Service pour générer des noms de fichiers sûrs
     * @param HttpClientInterface $httpClient Client HTTP pour vérifier le reCAPTCHA
     * @return Response La page d'inscription ou redirection après succès
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    #[Route('/register', name: 'app_register')]
    public function register(Request $request, UserPasswordHasherInterface $userPasswordHasher, Security $security, EntityManagerInterface $entityManager, TagRepository $tagRepository, SluggerInterface $slugger, HttpClientInterface $httpClient): Response
    {

        if ($this->getUser()) {
            return $this->redirectToRoute('app_home');
        }
        $user = new User();
        // Définir les questions dynamiques
        $dynamicQuestions = [
            'Pourquoi cette thématique de recherche vous intéresse-t-elle ?',
            'Pourquoi avez-vous souhaité être chercheur ?',
            'Qu\'aimez vous dans la recherche ?',
            'Quels sont les problèmes de recherche auxquels vous vous intéressez ?',
            'Quelles sont les méthodologies de recherche que vous utilisez dans votre domaine d\'étude ?',
            'Qu\'est ce qui, d\'après vous, vous a amené(e) à faire de la recherche ?',
            'Comment vous définirirez vous en tant que chercheur?',
            'Pensez-vous que ce choix ait un lien avec  un évènement de votre biographie ? (rencontre, auteur, environnement personnel, professionnel ....) et si oui pouvez-vous brièvement le/la décrire ?',
            'Pouvez-vous nous raconter qu\'est ce qui a motivé le choix  de vos thématiques de recherche ?',
            'Comment vos expériences personnelles ont-elles influencé votre choix de carrière et vos recherches en sciences humaines et sociales ?',
            'En quelques mots, en tant que chercheur(se) qu\'est ce qui vous anime ?',
            'Si vous deviez choisir 4 auteurs qui vous ont marquée, quels seraient-ils?',
            'Quelle est la phrase ou la citation qui vous représente le mieux ?',
        ];

        $taggableQuestions = [
            'Quels mot-clés peuvent être reliés à votre projet en cours ?',
            'Si vous deviez choisir 5 mots pour vous définir en tant que chercheur (se); quels seraient-ils?',
        ];

        $minQuestionsRequired = 3; // Nombre minimum de questions obligatoires

        $taggableMinChoices = [2, 5]; // Par exemple, 2 tags minimum pour chaque question taggable

        $tags = $tagRepository->findAllOrderedByName();

        // Créer le formulaire et passer les questions dynamiques comme option
        $form = $this->createForm(RegistrationFormType::class, $user, [
            'dynamic_questions' => $dynamicQuestions,
            'min_questions_required' => $minQuestionsRequired,
            'taggable_questions' => $taggableQuestions,
            'taggable_min_choices' => $taggableMinChoices,
            'tags' => $tags, // Passer les tags au formulaire
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $recaptchaResponse = $request->request->get('g-recaptcha-response');

            if (!$recaptchaResponse) {
                $this->addFlash('error', 'Veuillez confirmer que vous n’êtes pas un robot.');
                return $this->redirectToRoute('app_register');
            }

            // Appel l’API Google pour valider le token
            $response = $httpClient->request('POST', 'https://www.google.com/recaptcha/api/siteverify', [
                'body' => [
                    'secret' => $_ENV['RECAPTCHA_SECRET_KEY'],
                    'response' => $recaptchaResponse,
                    'remoteip' => $request->getClientIp(),
                ],
            ]);

            $data = $response->toArray();

            if (!$data['success'] || ($data['score'] ?? 0) < 0.5) {
                $this->addFlash('error', 'La vérification reCAPTCHA a échoué. Veuillez réessayer.');
                return $this->redirectToRoute('app_register');
            }

            /** @var string $plainPassword */
            $plainPassword = $form->get('plainPassword')->getData();

            // encode the plain password
            $user->setPassword($userPasswordHasher->hashPassword($user, $plainPassword));

            // Gestion de l'upload de la photo de profil
            $profileImageFile = $form->get('profileImageFile')->getData();
            if ($profileImageFile) {
                $originalFilename = pathinfo($profileImageFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename . '-' . uniqid() . '.' . $profileImageFile->guessExtension();

                try {
                    $profileImageFile->move(
                        $this->getParameter('kernel.project_dir') . '/assets/profile_images',
                        $newFilename
                    );
                } catch (FileException $e) {
                    $this->addFlash('danger', "Erreur lors de l'upload de la photo de profil.");
                }
                $user->setProfileImage($newFilename);
            }

            $entityManager->persist($user);

            // Handle user questions
            $questions = $form->get('userQuestions')->getData();

            foreach ($questions as $index => $questionText) {
                if (!empty($questionText)) {
                    $userQuestion = new UserQuestions();
                    $userQuestion->setUser($user);
                    $userQuestion->setQuestion('Question ' . $index);
                    $userQuestion->setAnswer($questionText);

                    $entityManager->persist($userQuestion);
                }
            }

            $taggableRaw = $form->get('taggableQuestions')->getData() ?? [];

            foreach ($taggableRaw as $index => $ids) {
                if (!is_array($ids)) {
                    $ids = $ids ? [$ids] : [];
                }
                foreach ($ids as $tagId) {
                    $tag = $tagRepository->find($tagId);
                    if ($tag) {
                        $userQuestion = new UserQuestions();
                        $userQuestion->setUser($user);
                        $userQuestion->setQuestion("Taggable Question $index");
                        $userQuestion->setAnswer($tag->getName());
                        $entityManager->persist($userQuestion);
                    }
                }
            }

            $entityManager->flush();

            // do anything else you need here, like send an email
            return $security->login($user, 'form_login', 'main');
        }

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form,
            'dynamic_questions' => $dynamicQuestions, // Passer les questions au template
            'min_questions_required' => $minQuestionsRequired, // Passer les questions au template
            'taggable_min_choices' => $taggableMinChoices, // Passer les questions au template
            'taggable_questions' => $taggableQuestions, // Passer les questions au template
        ]);
    }
}
