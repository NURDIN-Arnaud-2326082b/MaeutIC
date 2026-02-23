<?php

namespace App\Controller\Api;

use App\Entity\User;
use App\Entity\UserQuestions;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[Route('/api')]
class AuthApiController extends AbstractController
{
    #[Route('/login', name: 'api_login', methods: ['POST'])]
    public function login(
        Request $request, 
        UserRepository $userRepository,
        UserPasswordHasherInterface $passwordHasher,
        Security $security
    ): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        
        if (!isset($data['username']) || !isset($data['password'])) {
            return $this->json([
                'error' => 'Username and password required'
            ], Response::HTTP_BAD_REQUEST);
        }

        // Trouver l'utilisateur par username
        $user = $userRepository->findOneBy(['username' => $data['username']]);
        
        if (!$user) {
            return $this->json([
                'error' => 'Invalid credentials'
            ], Response::HTTP_UNAUTHORIZED);
        }

        // Vérifier le mot de passe
        if (!$passwordHasher->isPasswordValid($user, $data['password'])) {
            return $this->json([
                'error' => 'Invalid credentials'
            ], Response::HTTP_UNAUTHORIZED);
        }

        // Authentifier l'utilisateur (créer une session)
        $security->login($user, 'form_login', 'main');

        return $this->json([
            'user' => [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'username' => $user->getUsername(),
                'firstName' => $user->getFirstName(),
                'lastName' => $user->getLastName(),
                'profileImage' => $user->getProfileImage() 
                    ? '/profile_images/' . $user->getProfileImage()
                    : null,
                'userType' => $user->getUserType(),
            ]
        ]);
    }

    #[Route('/logout', name: 'api_logout', methods: ['POST'])]
    public function logout(Security $security): JsonResponse
    {
        $security->logout(false);
        return $this->json(['success' => true]);
    }

    #[Route('/me', name: 'api_me', methods: ['GET'])]
    public function me(): JsonResponse
    {
        /** @var User|null $user */
        $user = $this->getUser();
        
        if (!$user) {
            return $this->json(['user' => null], Response::HTTP_OK);
        }

        return $this->json([
            'user' => [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'username' => $user->getUsername(),
                'firstName' => $user->getFirstName(),
                'lastName' => $user->getLastName(),
                'profileImage' => $user->getProfileImage() 
                    ? '/profile_images/' . $user->getProfileImage()
                    : null,
                'userType' => $user->getUserType(),
            ]
        ]);
    }

    #[Route('/register', name: 'api_register', methods: ['POST'])]
    public function register(
        Request $request, 
        UserPasswordHasherInterface $passwordHasher,
        UserRepository $userRepository,
        EntityManagerInterface $entityManager
    ): JsonResponse
    {
        // Supporter à la fois JSON et FormData
        $contentType = $request->headers->get('Content-Type', '');
        
        if (str_contains($contentType, 'multipart/form-data')) {
            // FormData
            $data = $request->request->all();
        } else {
            // JSON
            $data = json_decode($request->getContent(), true);
        }
        
        error_log('Registration data: ' . json_encode($data));
        error_log('Content-Type: ' . $contentType);
        
        // Validation - utiliser 'plainPassword' si c'est du FormData, sinon 'password'
        $passwordField = isset($data['plainPassword']) ? 'plainPassword' : 'password';
        
        if (!isset($data['email']) || !isset($data[$passwordField])) {
            return $this->json([
                'error' => 'Email and password required',
                'received' => array_keys($data),
                'passwordField' => $passwordField
            ], 400);
        }

        // Check if email already exists
        if ($userRepository->findOneBy(['email' => $data['email']])) {
            return $this->json(['error' => 'Email already exists'], 400);
        }

        // Check if username already exists
        if (isset($data['username']) && $userRepository->findOneBy(['username' => $data['username']])) {
            return $this->json(['error' => 'Username already exists'], 400);
        }

        // Create user
        $user = new User();
        $user->setEmail($data['email']);
        
        if (isset($data['username'])) {
            $user->setUsername($data['username']);
        }
        if (isset($data['firstName'])) {
            $user->setFirstName($data['firstName']);
        }
        if (isset($data['lastName'])) {
            $user->setLastName($data['lastName']);
        }
        if (isset($data['genre'])) {
            $user->setGenre($data['genre']);
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
        
        $hashedPassword = $passwordHasher->hashPassword($user, $data[$passwordField]);
        $user->setPassword($hashedPassword);
        
        // Gérer l'upload de photo de profil
        $profileImageFile = $request->files->get('profileImage');
        if ($profileImageFile) {
            $originalFilename = pathinfo($profileImageFile->getClientOriginalName(), PATHINFO_FILENAME);
            // Créer un nom de fichier sécurisé
            $newFilename = uniqid() . '.' . $profileImageFile->guessExtension();
            
            try {
                $profileImageFile->move(
                    $this->getParameter('kernel.project_dir') . '/public/profile_images',
                    $newFilename
                );
                $user->setProfileImage($newFilename);
            } catch (\Exception $e) {
                error_log('Error uploading profile image: ' . $e->getMessage());
            }
        }
        
        $entityManager->persist($user);
        
        // Gérer les questions dynamiques si présentes
        if (isset($data['userQuestions'])) {
            $userQuestions = is_string($data['userQuestions']) 
                ? json_decode($data['userQuestions'], true) 
                : $data['userQuestions'];
                
            if (is_array($userQuestions)) {
                foreach ($userQuestions as $index => $answerText) {
                    if (!empty(trim($answerText))) {
                        $userQuestion = new UserQuestions();
                        $userQuestion->setUser($user);
                        $userQuestion->setQuestion('Question ' . $index);
                        $userQuestion->setAnswer($answerText);
                        $entityManager->persist($userQuestion);
                    }
                }
            }
        }
        
        // Gérer les questions taggables si présentes
        if (isset($data['taggableQuestions'])) {
            $taggableQuestions = is_string($data['taggableQuestions']) 
                ? json_decode($data['taggableQuestions'], true) 
                : $data['taggableQuestions'];
                
            if (is_array($taggableQuestions)) {
                foreach ($taggableQuestions as $index => $tags) {
                    if (is_array($tags)) {
                        foreach ($tags as $tagName) {
                            if (!empty(trim($tagName))) {
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
        }
        
        $entityManager->flush();

        return $this->json([
            'success' => true,
            'user' => [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'username' => $user->getUsername(),
            ]
        ], 201);
    }

    #[Route('/check-email', name: 'api_check_email', methods: ['GET'])]
    public function checkEmail(Request $request, UserRepository $userRepository): JsonResponse
    {
        $email = $request->query->get('email');

        if (!$email) {
            return new JsonResponse(['available' => true]);
        }

        $user = $userRepository->findOneBy(['email' => $email]);

        return new JsonResponse([
            'available' => $user === null,
            'message' => $user ? 'Cette adresse email est déjà utilisée' : 'Adresse email disponible'
        ]);
    }

    #[Route('/check-username', name: 'api_check_username', methods: ['GET'])]
    public function checkUsername(Request $request, UserRepository $userRepository): JsonResponse
    {
        $username = $request->query->get('username');

        if (!$username) {
            return new JsonResponse(['available' => true]);
        }

        $user = $userRepository->findOneBy(['username' => $username]);

        return new JsonResponse([
            'available' => $user === null,
            'message' => $user ? 'Ce nom d\'utilisateur est déjà pris' : 'Nom d\'utilisateur disponible'
        ]);
    }
}
