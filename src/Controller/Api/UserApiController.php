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

        return $this->json([
            'id' => $user->getId(),
            'username' => $user->getUsername(),
            'firstName' => $user->getFirstName(),
            'lastName' => $user->getLastName(),
            'email' => $user->getEmail(),
            'profileImage' => $user->getProfileImage() ? '/images/profile_images/' . $user->getProfileImage() : null,
            'affiliationLocation' => $user->getAffiliationLocation(),
            'specialization' => $user->getSpecialization(),
            'researchTopic' => $user->getResearchTopic(),
            'researcherTitle' => $user->getResearcherTitle(),
            'genre' => $user->getGenre(),
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
}
