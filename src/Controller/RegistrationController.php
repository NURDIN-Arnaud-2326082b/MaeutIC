<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\RegistrationFormType;
use App\Entity\UserQuestions;
use App\Repository\TagRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class RegistrationController extends AbstractController
{
    #[Route('/register', name: 'app_register')]
    public function register(
        Request $request,
        UserPasswordHasherInterface $userPasswordHasher,
        Security $security,
        EntityManagerInterface $entityManager,
        TagRepository $tagRepository,
        SluggerInterface $slugger,
        HttpClientInterface $httpClient
    ): Response {
        $user = new User();

        // Questions dynamiques
        $dynamicQuestions = [
            'Pourquoi cette thématique de recherche vous intéresse-t-elle ?',
            'Pourquoi avez-vous souhaité être chercheur ?',
            'Qu\'aimez vous dans la recherche ?',
            'Quels sont les problèmes de recherche auxquels vous vous intéressez ?',
            'Quelles sont les méthodologies de recherche que vous utilisez dans votre domaine d\'étude ?',
            'Qu\'est ce qui, d\'après vous, vous a amené(e) à faire de la recherche ?',
            'Comment vous définirirez vous en tant que chercheur?',
            'Pensez-vous que ce choix ait un lien avec  un évènement de votre biographie ? (rencontre, auteur, environnement personnel, professionnel ....) et si oui pouvez-vous brièvement le/la décrire ?',
            'Pouvez-vous nous raconter qu\'est ce qui a motivé le choix  de vos thématiques de recherche ?',
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

        $form = $this->createForm(RegistrationFormType::class, $user, [
            'dynamic_questions' => $dynamicQuestions,
            'min_questions_required' => $minQuestionsRequired,
            'taggable_questions' => $taggableQuestions,
            'taggable_min_choices' => $taggableMinChoices,
            'tags' => $tags,
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            $recaptchaResponse = $request->request->get('g-recaptcha-response');

            if (!$recaptchaResponse) {
                $this->addFlash('error', 'Veuillez confirmer que vous n’êtes pas un robot.');
                return $this->redirectToRoute('app_register');
            }

            // Appel l’API Google pour valider le token
            $response = $httpClient->request('POST', 'https://www.google.com/recaptcha/api/siteverify', [
                'body' => [
                    'secret' => $_ENV['6Lfx-gIsAAAAADs2WiCDrn0kn74HyGrdVtLb637C'],
                    'response' => $recaptchaResponse,
                    'remoteip' => $request->getClientIp(),
                ],
            ]);

            $data = $response->toArray();

            if (!$data['success'] || ($data['score'] ?? 0) < 0.5) {
                $this->addFlash('error', 'La vérification reCAPTCHA a échoué. Veuillez réessayer.');
                return $this->redirectToRoute('app_register');
            }

            if ($form->isValid()) {
                /** @var string $plainPassword */
                $plainPassword = $form->get('plainPassword')->getData();

                $user->setPassword($userPasswordHasher->hashPassword($user, $plainPassword));

                // Gestion de l'upload de la photo
                $profileImageFile = $form->get('profileImageFile')->getData();
                if ($profileImageFile) {
                    $originalFilename = pathinfo($profileImageFile->getClientOriginalName(), PATHINFO_FILENAME);
                    $safeFilename = $slugger->slug($originalFilename);
                    $newFilename = $safeFilename.'-'.uniqid().'.'.$profileImageFile->guessExtension();

                    try {
                        $profileImageFile->move(
                            $this->getParameter('kernel.project_dir').'/assets/profile_images',
                            $newFilename
                        );
                    } catch (FileException $e) {
                        $this->addFlash('danger', "Erreur lors de l'upload de la photo de profil.");
                    }
                    $user->setProfileImage($newFilename);
                }

                $entityManager->persist($user);

                // Sauvegarde des questions utilisateur
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

                return $security->login($user, 'form_login', 'main');
            }
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