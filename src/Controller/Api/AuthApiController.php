<?php

namespace App\Controller\Api;

use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[Route('/api')]
class AuthApiController extends AbstractController
{
    #[Route('/login', name: 'api_login', methods: ['POST'])]
    public function login(Request $request, AuthenticationUtils $authenticationUtils): JsonResponse
    {
        // This is handled by Symfony's security system
        // If we reach here, authentication was successful
        /** @var User|null $user */
        $user = $this->getUser();
        
        if (!$user) {
            return $this->json([
                'error' => 'Invalid credentials'
            ], 401);
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

    #[Route('/logout', name: 'api_logout', methods: ['POST'])]
    public function logout(): JsonResponse
    {
        // Handled by Symfony's security system
        return $this->json(['success' => true]);
    }

    #[Route('/register', name: 'api_register', methods: ['POST'])]
    public function register(
        Request $request, 
        UserPasswordHasherInterface $passwordHasher,
        UserRepository $userRepository
    ): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        
        // Validation
        if (!isset($data['email']) || !isset($data['password'])) {
            return $this->json(['error' => 'Email and password required'], 400);
        }

        // Check if email already exists
        if ($userRepository->findOneBy(['email' => $data['email']])) {
            return $this->json(['error' => 'Email already exists'], 400);
        }

        // Check if username already exists
        if ($userRepository->findOneBy(['username' => $data['username']])) {
            return $this->json(['error' => 'Username already exists'], 400);
        }

        // Create user (you'll need to create the User entity method)
        // This is a placeholder - adapt to your User entity
        /*
        $user = new User();
        $user->setEmail($data['email']);
        $user->setUsername($data['username']);
        $user->setFirstName($data['firstName']);
        $user->setLastName($data['lastName']);
        $hashedPassword = $passwordHasher->hashPassword($user, $data['password']);
        $user->setPassword($hashedPassword);
        
        $entityManager->persist($user);
        $entityManager->flush();
        */

        return $this->json(['success' => true], 201);
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
