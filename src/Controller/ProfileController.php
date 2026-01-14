<?php

/**
 * Contrôleur de gestion des profils utilisateurs
 *
 * Gère toutes les opérations liées aux profils utilisateurs :
 * - Affichage du profil personnel et des autres utilisateurs
 * - Édition du profil
 * - Consultation des posts et commentaires d'un utilisateur
 * - Suppression de compte
 */

namespace App\Controller;

use App\Entity\User;
use App\Entity\UserQuestions;
use App\Form\ProfileEditFormType;
use App\Repository\CommentRepository;
use App\Repository\PostLikeRepository;
use App\Repository\PostRepository;
use App\Repository\TagRepository;
use App\Repository\UserQuestionsRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\String\Slugger\AsciiSlugger;

final class ProfileController extends AbstractController
{
    /**
     * Affiche le profil de l'utilisateur connecté
     *
     * @param Security $security Service de sécurité pour récupérer l'utilisateur
     * @return Response Le profil de l'utilisateur ou redirection vers login
     */
    #[Route('/profile', name: 'app_profile')]
    public function index(Security $security): Response
    {
        $user = $security->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }
        return $this->renderProfile($user);
    }

    /**
     * Méthode privée pour générer le rendu d'un profil avec toutes ses données
     *
     * @param User $user L'utilisateur dont le profil doit être affiché
     * @return Response Le profil rendu avec questions et labels
     */
    private function renderProfile(User $user): Response
    {
        // Récupérer les réponses aux questions
        $userQuestions = [];
        foreach ($user->getUserQuestions() as $question) {
            $userQuestions[$question->getQuestion()][] = $question->getAnswer();
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

    /**
     * Affiche le profil public d'un utilisateur spécifique
     *
     * @param string $username Le nom d'utilisateur à afficher
     * @param UserRepository $userRepository Repository des utilisateurs
     * @return Response Le profil de l'utilisateur demandé
     */
    #[Route('/profile/show/{username}', name: 'app_profile_show')]
    public function show(string $username, UserRepository $userRepository): Response
    {
        $user = $userRepository->findOneBy(['username' => $username]);
        if (!$user) {
            throw $this->createNotFoundException('Utilisateur non trouvé');
        }
        return $this->renderProfile($user);
    }

    /**
     * Récupère et affiche tous les posts d'un utilisateur
     *
     * @param string $username Le nom d'utilisateur
     * @param UserRepository $userRepository Repository des utilisateurs
     * @param PostRepository $postRepository Repository des posts
     * @return Response Fragment HTML avec la liste des posts
     */
    #[Route('/profile/posts/{username}', name: 'app_profile_posts')]
    public function posts(string $username, UserRepository $userRepository, PostRepository $postRepository): Response
    {
        $user = $userRepository->findOneBy(['username' => $username]);
        $posts = [];
        if ($user) {
            // order by creationDate descending so recent posts first
            $posts = $postRepository->findBy(['user' => $user], ['creationDate' => 'DESC']);
        }
        return $this->render('profile/_posts.html.twig', [
            'posts' => $posts,
        ]);
    }

    /**
     * Récupère et affiche tous les commentaires d'un utilisateur
     *
     * @param string $username Le nom d'utilisateur
     * @param UserRepository $userRepository Repository des utilisateurs
     * @param CommentRepository $commentRepository Repository des commentaires
     * @return Response Fragment HTML avec la liste des commentaires
     */
    #[Route('/profile/comments/{username}', name: 'app_profile_comments')]
    public function comments(string $username, UserRepository $userRepository, CommentRepository $commentRepository): Response
    {
        $user = $userRepository->findOneBy(['username' => $username]);
        $comments = [];
        if ($user) {
            $comments = $commentRepository->findBy(['user' => $user]);
        }
        return $this->render('profile/_comments.html.twig', [
            'comments' => $comments,
        ]);
    }

    /**
     * Édite le profil d'un utilisateur
     *
     * Permet de modifier les informations du profil, la photo, et les réponses
     * aux questions dynamiques et taggables. Seul l'utilisateur propriétaire
     * peut éditer son propre profil.
     *
     * @param Request $request La requête HTTP avec les données du formulaire
     * @param EntityManagerInterface $entityManager Gestionnaire d'entités
     * @param TagRepository $tagRepository Repository des tags
     * @param UserQuestionsRepository $userQuestionsRepository Repository des questions utilisateur
     * @return Response Le formulaire d'édition ou redirection après succès
     */
    #[Route('/profile/edit', name: 'app_profile_edit')]
    public function edit(
        Request                 $request,
        EntityManagerInterface  $entityManager,
        TagRepository           $tagRepository,
        UserQuestionsRepository $userQuestionsRepository
    ): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_home');
        }
//        $user = $userRepository->findOneBy(['username' => $username]);
//        if (!$user) {
//            throw $this->createNotFoundException('Utilisateur non trouvé');
//        }
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
                $index = (int)filter_var($uq->getQuestion(), FILTER_SANITIZE_NUMBER_INT);
                $tag = $tagRepository->findOneBy(['name' => $uq->getAnswer()]);
                if ($tag) {
                    $taggableQuestionsData[$index][] = $tag->getId();
                }
            } else {
                $index = (int)filter_var($uq->getQuestion(), FILTER_SANITIZE_NUMBER_INT);
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
                $safeFilename = (new AsciiSlugger())->slug($originalFilename);
                $newFilename = $safeFilename . '-' . uniqid() . '.' . $profileImageFile->guessExtension();
                try {
                    $profileImageFile->move(
                        $this->getParameter('kernel.project_dir') . '/public/profile_images',
                        $newFilename
                    );
                } catch (Exception $e) {
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
                    $uq = new UserQuestions();
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
                        $uq = new UserQuestions();
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

    /**
     * Supprime le compte de l'utilisateur connecté
     *
     * Supprime définitivement le compte utilisateur après vérification du token CSRF,
     * invalide la session et déconnecte l'utilisateur
     *
     * @param Security $security Service de sécurité
     * @param Request $request La requête contenant le token CSRF
     * @param TokenStorageInterface $tokenStorage Stockage des tokens d'authentification
     * @param SessionInterface $session La session utilisateur
     * @param EntityManagerInterface $entityManager Gestionnaire d'entités
     * @return Response Redirection vers l'accueil
     */
    #[Route('/profile/delete', name: 'app_profile_delete', methods: ['POST'])]
    public function delete(
        Security               $security,
        Request                $request,
        TokenStorageInterface  $tokenStorage,
        SessionInterface       $session,
        EntityManagerInterface $entityManager
    ): Response
    {
        $user = $security->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        if ($this->isCsrfTokenValid('delete-user', $request->request->get('_token'))) {
            $tokenStorage->setToken(null);
            $entityManager->remove($user);
            $entityManager->flush();
            $session->invalidate();

            $this->addFlash('success', 'Votre compte a été supprimé.');
            return $this->redirectToRoute('app_home');
        }

        $this->addFlash('error', 'Token CSRF invalide.');
        return $this->redirectToRoute('app_home');
    }

    // #[Route('/profile/delete/confirm', name: 'app_profile_delete_confirm')]
    // public function confirmDelete(
    //     SessionInterface $session,
    //     EntityManagerInterface $entityManager
    // ): Response {
    //     $id = $session->get('user_to_delete_id');
    //     $session->remove('user_to_delete_id');

    //     if ($id) {
    //         $user = $entityManager->getRepository(User::class)->find($id);
    //         if ($user) {
    //             $entityManager->remove($user);
    //             $entityManager->flush();
    //         }
    //     }

    //     $this->addFlash('success', 'Votre compte a été supprimé.');
    //     return $this->redirectToRoute('app_home');
    // }

    #[Route('/profile/replies', name: 'app_profile_replies')]
    public function replies(
        PostRepository     $postRepository,
        PostLikeRepository $postLikeRepository
    ): Response
    {
        $user = $this->getUser();
        if (!$user) {
            throw $this->createAccessDeniedException('Vous devez être connecté pour accéder à cette page.');
        }

        $replies = $postRepository->findRepliesByUser($user);

        // Initialiser les données de likes pour les réponses
        $repliesLikes = [];
        $userRepliesLikes = [];

        foreach ($replies as $reply) {
            $repliesLikes[$reply->getId()] = $postLikeRepository->countByPost($reply);
            $userRepliesLikes[$reply->getId()] = $postLikeRepository->isLikedByUser($reply, $user);
        }

        return $this->render('profile/replies.html.twig', [
            'user' => $user,
            'replies' => $replies,
            'repliesLikes' => $repliesLikes,
            'userRepliesLikes' => $userRepliesLikes,
        ]);
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