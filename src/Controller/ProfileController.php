<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\SecurityBundle\Security;
use App\Entity\UserQuestions;
use App\Repository\PostRepository;
use App\Repository\CommentRepository;
use Symfony\Component\HttpFoundation\Request;
use App\Form\ProfileEditFormType;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\TagRepository;
use App\Repository\UserQuestionsRepository;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use App\Repository\UserRepository;

final class ProfileController extends AbstractController{
    #[Route('/profile', name: 'app_profile')]
    public function index(Security $security): Response
    {
        $user = $security->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }
        return $this->renderProfile($user);
    }

    #[Route('/profile/show/{username}', name: 'app_profile_show')]
    public function show(string $username, UserRepository $userRepository): Response
    {
        $user = $userRepository->findOneBy(['username' => $username]);
        if (!$user) {
            throw $this->createNotFoundException('Utilisateur non trouvé');
        }
        return $this->renderProfile($user);
    }

    private function renderProfile($user): Response
    {
        // Récupérer les réponses aux questions
        $userQuestions = [];
        if ($user) {
            foreach ($user->getUserQuestions() as $question) {
                $userQuestions[$question->getQuestion()][] = $question->getAnswer();
            }
        }

        // Libellés des questions classiques
        $questionLabels = [
            'Question 0' => 'Pourquoi cette thématique de recherche vous intéresse-t-elle ?',
            'Question 1' => 'Pourquoi avez-vous souhaité être chercheur ?',
            'Question 2' => 'Qu\'aimez-vous dans la recherche ?',
            'Question 3' => 'Quels sont les problèmes de recherche auxquels vous vous intéressez ?',
            'Question 4' => 'Quelles sont les méthodologies de recherche que vous utilisez dans votre domaine d\'étude ?',
            'Question 5' => 'Qu\'est-ce qui, d\'après vous, vous a amené(e) à faire de la recherche ?',
            'Question 6' => 'Comment vous définiriez-vous en tant que chercheur ?',
            'Question 7' => 'Pensez-vous que ce choix ait un lien avec un évènement de votre biographie ?',
            'Question 8' => 'Pouvez-vous nous raconter ce qui a motivé le choix de vos thématiques de recherche ?',
            'Question 9' => 'Comment vos expériences personnelles ont-elles influencé votre choix de carrière et vos recherches en sciences humaines et sociales ?',
            'Question 10' => 'En quelques mots, en tant que chercheur(se) qu\'est-ce qui vous anime ?',
            'Question 11' => 'Si vous deviez choisir 4 auteurs qui vous ont marquée, quels seraient-ils ?',
            'Question 12' => 'Quelle est la phrase ou la citation qui vous représente le mieux ?',
        ];

        // Libellés des questions taggables
        $taggableLabels = [
            'Taggable Question 0' => 'Quels mot-clés peuvent être reliés à votre projet en cours ?',
            'Taggable Question 1' => 'Si vous deviez choisir 5 mots pour vous définir en tant que chercheur(se), quels seraient-ils ?',
        ];

        return $this->render('profile/index.html.twig', [
            'user' => $user,
            'userQuestions' => $userQuestions,
            'questionLabels' => $questionLabels,
            'taggableLabels' => $taggableLabels,
        ]);
    }

    #[Route('/profile/posts', name: 'app_profile_posts')]
    public function posts(Security $security, PostRepository $postRepository): Response
    {
        $user = $security->getUser();
        $posts = [];
        if ($user) {
            $posts = $postRepository->findBy(['user' => $user]);
        }
        return $this->render('profile/_posts.html.twig', [
            'posts' => $posts,
        ]);
    }

    #[Route('/profile/comments/{username}', name: 'app_profile_comments')]
    public function comments(Security $security, CommentRepository $commentRepository): Response
    {
        $user = $security->getUser();
        $comments = [];
        if ($user) {
            $comments = $commentRepository->findBy(['user' => $user]);
        }
        return $this->render('profile/_comments.html.twig', [
            'comments' => $comments,
        ]);
    }

    #[Route('/profile/edit/{username}', name: 'app_profile_edit')]
    public function edit(
        string $username,
        UserRepository $userRepository,
        Request $request,
        EntityManagerInterface $entityManager,
        TagRepository $tagRepository,
        UserQuestionsRepository $userQuestionsRepository
    ): Response {
        $user = $userRepository->findOneBy(['username' => $username]);
        if (!$user) {
            throw $this->createNotFoundException('Utilisateur non trouvé');
        }
        // Questions classiques
        $dynamicQuestions = [
            'Pourquoi cette thématique de recherche vous intéresse-t-elle ?',
            'Pourquoi avez-vous souhaité être chercheur ?',
            'Qu\'aimez-vous dans la recherche ?',
            'Quels sont les problèmes de recherche auxquels vous vous intéressez ?',
            'Quelles sont les méthodologies de recherche que vous utilisez dans votre domaine d\'étude ?',
            'Qu\'est-ce qui, d\'après vous, vous a amené(e) à faire de la recherche ?',
            'Comment vous définiriez-vous en tant que chercheur ?',
            'Pensez-vous que ce choix ait un lien avec un évènement de votre biographie ?',
            'Pouvez-vous nous raconter ce qui a motivé le choix de vos thématiques de recherche ?',
            'Comment vos expériences personnelles ont-elles influencé votre choix de carrière et vos recherches en sciences humaines et sociales ?',
            'En quelques mots, en tant que chercheur(se) qu\'est-ce qui vous anime ?',
            'Si vous deviez choisir 4 auteurs qui vous ont marquée, quels seraient-ils ?',
            'Quelle est la phrase ou la citation qui vous représente le mieux ?',
        ];
        // Questions taggables
        $taggableQuestions = [
            'Quels mot-clés peuvent être reliés à votre projet en cours ?',
            'Si vous deviez choisir 5 mots pour vous définir en tant que chercheur(se), quels seraient-ils ?'
        ];
        $tags = $tagRepository->findAll();

        // Pré-remplir les réponses actuelles
        $userQuestions = $userQuestionsRepository->findAllByUser($user->getId());
        $userQuestionsData = [];
        $taggableQuestionsData = [[], []];
        foreach ($userQuestions as $uq) {
            if (str_starts_with($uq->getQuestion(), 'Taggable')) {
                $index = (int) filter_var($uq->getQuestion(), FILTER_SANITIZE_NUMBER_INT);
                $tag = $tagRepository->findOneBy(['name' => $uq->getAnswer()]);
                if ($tag) {
                    $taggableQuestionsData[$index][] = $tag->getId();
                }
            } else {
                $index = (int) filter_var($uq->getQuestion(), FILTER_SANITIZE_NUMBER_INT);
                $userQuestionsData[$index] = $uq->getAnswer();
            }
        }
        // S'assurer que toutes les questions sont présentes (même sans réponse)
        foreach ($dynamicQuestions as $i => $q) {
            if (!array_key_exists($i, $userQuestionsData)) {
                $userQuestionsData[$i] = '';
            }
        }
        foreach ($taggableQuestions as $i => $q) {
            if (!array_key_exists($i, $taggableQuestionsData)) {
                $taggableQuestionsData[$i] = [];
            }
        }
        // Trier les questions pour garantir l'ordre
        ksort($userQuestionsData);
        ksort($taggableQuestionsData);
        // Correction : Pré-remplir avec des objets Tag
        $taggableQuestionsObjects = [[], []];
        foreach ($taggableQuestionsData as $i => $ids) {
            foreach ($ids as $tagId) {
                $tag = $tagRepository->find($tagId);
                if ($tag) {
                    $taggableQuestionsObjects[$i][] = $tag;
                }
            }
        }
        $form = $this->createForm(ProfileEditFormType::class, $user, [
            'dynamic_questions' => $dynamicQuestions,
            'taggable_questions' => $taggableQuestions,
            'tags' => $tags,
        ]);
        $form->get('userQuestions')->setData($userQuestionsData);
        $form->get('taggableQuestions')->setData($taggableQuestionsObjects);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            // Gestion de l'upload de la photo de profil
            $profileImageFile = $form->get('profileImageFile')->getData();
            if ($profileImageFile) {
                $originalFilename = pathinfo($profileImageFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = (new \Symfony\Component\String\Slugger\AsciiSlugger())->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$profileImageFile->guessExtension();
                try {
                    $profileImageFile->move(
                        $this->getParameter('kernel.project_dir').'/public/profile_images',
                        $newFilename
                    );
                } catch (\Exception $e) {
                    $this->addFlash('danger', "Erreur lors de l'upload de la photo de profil.");
                }
                $user->setProfileImage($newFilename);
            }
            // Mettre à jour les infos de base
            $entityManager->persist($user);
            // Vider la collection Doctrine pour éviter les doublons en mémoire
            $user->getUserQuestions()->clear();
            // Supprimer toutes les anciennes réponses (classiques et taggables)
            foreach ($userQuestions as $uq) {
                $entityManager->remove($uq);
            }
            $entityManager->flush();
            // Ajouter les nouvelles réponses
            $questions = $form->get('userQuestions')->getData();
            foreach ($questions as $index => $answer) {
                if (!empty($answer)) {
                    $uq = new \App\Entity\UserQuestions();
                    $uq->setUser($user);
                    $uq->setQuestion('Question ' . $index);
                    $uq->setAnswer($answer);
                    $entityManager->persist($uq);
                }
            }
            $taggableRaw = $form->get('taggableQuestions')->getData() ?? [];
            foreach ($taggableRaw as $index => $tagsArray) {
                if (!is_array($tagsArray)) {
                    $tagsArray = $tagsArray ? [$tagsArray] : [];
                }
                $already = [];
                foreach ($tagsArray as $tag) {
                    if ($tag && !in_array($tag->getName(), $already, true)) {
                        $uq = new \App\Entity\UserQuestions();
                        $uq->setUser($user);
                        $uq->setQuestion('Taggable Question ' . $index);
                        $uq->setAnswer($tag->getName());
                        $entityManager->persist($uq);
                        $already[] = $tag->getName();
                    }
                }
            }
            $entityManager->flush();
            $this->addFlash('success', 'Profil mis à jour avec succès.');
            return $this->redirectToRoute('app_profile_show', ['username' => $user->getUsername()]);
        }
        return $this->render('profile/edit.html.twig', [
            'editForm' => $form->createView(),
            'dynamic_questions' => $dynamicQuestions,
            'taggable_questions' => $taggableQuestions,
        ]);
    }

    #[Route('/profile/delete', name: 'app_profile_delete', methods: ['POST'])]
    public function delete(
        Security $security,
        Request $request,
        SessionInterface $session,
        TokenStorageInterface $tokenStorage
    ): Response {
        $user = $security->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        if ($this->isCsrfTokenValid('delete-user', $request->request->get('_token'))) {

            $session->set('user_to_delete_id', $user->getId());

            $tokenStorage->setToken(null);

            return $this->redirectToRoute('app_profile_delete_confirm');
        }

        $this->addFlash('error', 'Token CSRF invalide.');
        return $this->redirectToRoute('app_home');
    }

    #[Route('/profile/delete/confirm', name: 'app_profile_delete_confirm')]
    public function confirmDelete(
        SessionInterface $session,
        EntityManagerInterface $entityManager
    ): Response {
        $id = $session->get('user_to_delete_id');
        $session->remove('user_to_delete_id');

        if ($id) {
            $user = $entityManager->getRepository(\App\Entity\User::class)->find($id);
            if ($user) {
                $entityManager->remove($user);
                $entityManager->flush();
            }
        }

        $session->invalidate();

        $this->addFlash('success', 'Votre compte a été supprimé.');
        return $this->redirectToRoute('app_home');
    }

    private function findTagIdByName($tags, $name)
    {
        foreach ($tags as $tag) {
            if ($tag->getName() === $name) {
                return $tag->getId();
            }
        }
        return null;
    }
}
