<?php

namespace App\Controller\Api;

use App\Entity\User;
use App\Entity\UserQuestions;
use App\Repository\UserRepository;
use App\Repository\TagRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileException;

#[Route('/api')]
class UserApiController extends AbstractController
{
    /**
     * Get user profile by username
     */
    #[Route('/profile/{username}', name: 'api_user_profile', methods: ['GET'])]
    public function getProfile(string $username, UserRepository $userRepository): JsonResponse
    {
        $user = $userRepository->findOneBy(['username' => $username]);

        if (!$user) {
            return $this->json(['error' => 'User not found'], Response::HTTP_NOT_FOUND);
        }

        /** @var User|null $currentUser */
        $currentUser = $this->getUser();
        $isInNetwork = false;
        $isBlocked = false;

        if ($currentUser) {
            $isInNetwork = $currentUser->isInNetwork($user->getId());
            $isBlocked = $currentUser->isBlocked($user->getId());
        }

        return $this->json([
            'id' => $user->getId(),
            'username' => $user->getUsername(),
            'firstName' => $user->getFirstName(),
            'lastName' => $user->getLastName(),
            'email' => $user->getEmail(),
            'profileImage' => $user->getProfileImage() ? '/profile_images/' . $user->getProfileImage() : null,
            'affiliationLocation' => $user->getAffiliationLocation(),
            'specialization' => $user->getSpecialization(),
            'researchTopic' => $user->getResearchTopic(),
            'researcherTitle' => $user->getResearcherTitle(),
            'genre' => $user->getGenre(),
            'isInNetwork' => $isInNetwork,
            'isBlocked' => $isBlocked,
        ]);
    }

    /**
     * Register a new user
     */
    #[Route('/register', name: 'api_user_register', methods: ['POST'])]
    public function register(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $entityManager,
        TagRepository $tagRepository,
        SluggerInterface $slugger,
        UserRepository $userRepository
    ): JsonResponse {
        $data = $request->request->all();

        // Log pour debug
        error_log('Registration data received: ' . json_encode($data));
        error_log('Files received: ' . json_encode(array_keys($request->files->all())));
        error_log('Content-Type: ' . $request->headers->get('Content-Type'));

        // Validation basique - vérifier que les champs ne sont pas null ET ne sont pas vides
        $missingFields = [];
        if (!$request->request->has('email') || trim($request->request->get('email')) === '') {
            $missingFields[] = 'email';
        }
        if (!$request->request->has('username') || trim($request->request->get('username')) === '') {
            $missingFields[] = 'username';
        }
        if (!$request->request->has('plainPassword') || trim($request->request->get('plainPassword')) === '') {
            $missingFields[] = 'plainPassword';
        }

        if (!empty($missingFields)) {
            return $this->json([
                'message' => 'Champs obligatoires manquants: ' . implode(', ', $missingFields),
                'received' => array_keys($data),
                'values' => $data
            ], Response::HTTP_BAD_REQUEST);
        }

        // Vérifier si l'email existe déjà
        if ($userRepository->findOneBy(['email' => $data['email']])) {
            return $this->json(['message' => 'Cet email est déjà utilisé'], Response::HTTP_CONFLICT);
        }

        // Vérifier si le username existe déjà
        if ($userRepository->findOneBy(['username' => $data['username']])) {
            return $this->json(['message' => 'Ce nom d\'utilisateur est déjà pris'], Response::HTTP_CONFLICT);
        }

        $user = new User();
        $user->setEmail($data['email']);
        $user->setUsername($data['username']);
        $user->setFirstName($data['firstName'] ?? '');
        $user->setLastName($data['lastName'] ?? '');
        if (isset($data['genre'])) {
            $user->setGenre($data['genre']);
        }
        $user->setAffiliationLocation($data['affiliationLocation'] ?? null);
        $user->setSpecialization($data['specialization'] ?? null);
        $user->setResearchTopic($data['researchTopic'] ?? null);

        // Hash le mot de passe
        $hashedPassword = $passwordHasher->hashPassword($user, $data['plainPassword']);
        $user->setPassword($hashedPassword);

        // Gestion de l'upload de la photo de profil
        $profileImageFile = $request->files->get('profileImage');
        if ($profileImageFile) {
            $originalFilename = pathinfo($profileImageFile->getClientOriginalName(), PATHINFO_FILENAME);
            $safeFilename = $slugger->slug($originalFilename);
            $newFilename = $safeFilename . '-' . uniqid() . '.' . $profileImageFile->guessExtension();

            try {
                $profileImageFile->move(
                    $this->getParameter('kernel.project_dir') . '/public/profile_images',
                    $newFilename
                );
                $user->setProfileImage($newFilename);
            } catch (FileException $e) {
                return $this->json(['message' => 'Erreur lors de l\'upload de la photo'], Response::HTTP_INTERNAL_SERVER_ERROR);
            }
        }

        $entityManager->persist($user);

        // Gestion des questions dynamiques
        if (isset($data['userQuestions'])) {
            $userQuestions = json_decode($data['userQuestions'], true);
            if (is_array($userQuestions)) {
                foreach ($userQuestions as $index => $answerText) {
                    if (!empty($answerText)) {
                        $userQuestion = new UserQuestions();
                        $userQuestion->setUser($user);
                        $userQuestion->setQuestion('Question ' . $index);
                        $userQuestion->setAnswer($answerText);
                        $entityManager->persist($userQuestion);
                    }
                }
            }
        }

        // Gestion des questions taggables
        if (isset($data['taggableQuestions'])) {
            $taggableQuestions = json_decode($data['taggableQuestions'], true);
            if (is_array($taggableQuestions)) {
                foreach ($taggableQuestions as $index => $tags) {
                    if (is_array($tags)) {
                        foreach ($tags as $tagName) {
                            $userQuestion = new UserQuestions();
                            $userQuestion->setUser($user);
                            $userQuestion->setQuestion("Taggable Question $index");
                            $userQuestion->setAnswer($tagName);
                            $entityManager->persist($userQuestion);
                        }
                    }
                }
            }
        }

        $entityManager->flush();

        return $this->json([
            'message' => 'Inscription réussie',
            'user' => [
                'id' => $user->getId(),
                'username' => $user->getUsername(),
                'email' => $user->getEmail(),
            ]
        ], Response::HTTP_CREATED);
    }

    /**
     * Check if email is available
     */
    #[Route('/check-email', name: 'api_check_email', methods: ['GET'])]
    public function checkEmail(Request $request, UserRepository $userRepository): JsonResponse
    {
        $email = $request->query->get('email');

        if (!$email) {
            return $this->json(['available' => true]);
        }

        $exists = $userRepository->findOneBy(['email' => $email]) !== null;

        return $this->json([
            'available' => !$exists,
            'message' => $exists ? 'Cet email est déjà utilisé' : 'Email disponible'
        ]);
    }

    /**
     * Check if username is available
     */
    #[Route('/check-username', name: 'api_check_username', methods: ['GET'])]
    public function checkUsername(Request $request, UserRepository $userRepository): JsonResponse
    {
        $username = $request->query->get('username');

        if (!$username) {
            return $this->json(['available' => true]);
        }

        $exists = $userRepository->findOneBy(['username' => $username]) !== null;

        return $this->json([
            'available' => !$exists,
            'message' => $exists ? 'Ce nom d\'utilisateur est déjà pris' : 'Nom d\'utilisateur disponible'
        ]);
    }

    /**
     * Update user profile
     */
    #[Route('/profile', name: 'api_user_update_profile', methods: ['PUT'])]
    public function updateProfile(
        Request $request,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        /** @var User|null $user */
        $user = $this->getUser();

        if (!$user) {
            return $this->json(['error' => 'Non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        $data = json_decode($request->getContent(), true);

        if (isset($data['firstName'])) {
            $user->setFirstName($data['firstName']);
        }
        if (isset($data['lastName'])) {
            $user->setLastName($data['lastName']);
        }
        if (isset($data['affiliationLocation'])) {
            $user->setAffiliationLocation($data['affiliationLocation']);
        }
        if (isset($data['specialization'])) {
            $user->setSpecialization($data['specialization']);
        }
        if (isset($data['researchTopic'])) {
            $user->setResearchTopic($data['researchTopic']);
        }

        $entityManager->flush();

        return $this->json(['message' => 'Profil mis à jour avec succès']);
    }

    /**
     * Get user profile overview (questions and tags)
     */
    #[Route('/profile/{username}/overview', name: 'api_user_profile_overview', methods: ['GET'])]
    public function getProfileOverview(string $username, UserRepository $userRepository, EntityManagerInterface $entityManager): JsonResponse
    {
        $user = $userRepository->findOneBy(['username' => $username]);

        if (!$user) {
            return $this->json(['error' => 'User not found'], Response::HTTP_NOT_FOUND);
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

        // Récupérer les questions de l'utilisateur
        $userQuestions = $entityManager->getRepository(UserQuestions::class)->findBy(['user' => $user]);

        $questions = [];
        $tags = [];

        foreach ($userQuestions as $uq) {
            $questionKey = $uq->getQuestion();
            $answer = $uq->getAnswer();

            if (strpos($questionKey, 'Taggable Question') !== false) {
                // C'est une question taggable
                if (!isset($tags[$questionKey])) {
                    $tags[$questionKey] = [
                        'label' => $taggableLabels[$questionKey] ?? $questionKey,
                        'tags' => []
                    ];
                }
                $tags[$questionKey]['tags'][] = $answer;
            } else {
                // C'est une question normale
                if (!isset($questions[$questionKey])) {
                    $questions[$questionKey] = [
                        'label' => $questionLabels[$questionKey] ?? $questionKey,
                        'answer' => $answer
                    ];
                }
            }
        }

        return $this->json([
            'questions' => array_values($questions),
            'tags' => array_values($tags)
        ]);
    }

    /**
     * Get user posts
     */
    #[Route('/profile/{username}/posts', name: 'api_user_posts', methods: ['GET'])]
    public function getUserPosts(string $username, UserRepository $userRepository, EntityManagerInterface $entityManager): JsonResponse
    {
        $user = $userRepository->findOneBy(['username' => $username]);

        if (!$user) {
            return $this->json(['error' => 'User not found'], Response::HTTP_NOT_FOUND);
        }

        $posts = $entityManager->getRepository(\App\Entity\Post::class)->findBy(
            ['user' => $user],
            ['creationDate' => 'DESC']
        );

        $result = [];
        foreach ($posts as $post) {
            $forum = $post->getForum();
            $result[] = [
                'id' => $post->getId(),
                'title' => $post->getName(),
                'category' => $forum->getTitle(),
                'forumId' => $forum->getId(),
                'creationDate' => $post->getCreationDate()->format('Y-m-d H:i:s'),
            ];
        }

        return $this->json($result);
    }

    /**
     * Get user comments
     */
    #[Route('/profile/{username}/comments', name: 'api_user_comments', methods: ['GET'])]
    public function getUserComments(string $username, UserRepository $userRepository, EntityManagerInterface $entityManager): JsonResponse
    {
        $user = $userRepository->findOneBy(['username' => $username]);

        if (!$user) {
            return $this->json(['error' => 'User not found'], Response::HTTP_NOT_FOUND);
        }

        $comments = $entityManager->getRepository(\App\Entity\Comment::class)->findBy(
            ['user' => $user],
            ['creationDate' => 'DESC']
        );

        $result = [];
        foreach ($comments as $comment) {
            $post = $comment->getPost();
            $forum = $post->getForum();
            $result[] = [
                'id' => $comment->getId(),
                'body' => $comment->getBody(),
                'creationDate' => $comment->getCreationDate()->format('Y-m-d H:i:s'),
                'postId' => $post->getId(),
                'postTitle' => $post->getName(),
                'forum' => $forum->getTitle(),
                'forumId' => $forum->getId(),
            ];
        }

        return $this->json($result);
    }

    /**
     * Delete user account
     */
    #[Route('/profile', name: 'api_user_delete_account', methods: ['DELETE'])]
    public function deleteAccount(EntityManagerInterface $entityManager): JsonResponse
    {
        /** @var User|null $user */
        $user = $this->getUser();

        if (!$user) {
            return $this->json(['error' => 'Non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        $entityManager->remove($user);
        $entityManager->flush();

        // Invalider la session
        $this->container->get('session')->invalidate();

        return $this->json(['message' => 'Compte supprimé avec succès']);
    }
}
