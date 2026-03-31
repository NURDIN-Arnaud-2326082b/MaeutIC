<?php

namespace App\Controller\Api;

use App\Entity\Tag;
use App\Repository\TagRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Repository\UserRepository;

#[Route('/api/admin')]
class AdminApiController extends AbstractController
{
    /**
     * Get all tags, optionally filtered by search query
     */
    #[Route('/tags', name: 'api_admin_tags_list', methods: ['GET'])]
    public function getTags(Request $request, TagRepository $tagRepository): JsonResponse
    {
        /** @var \App\Entity\User|null $user */
        $user = $this->getUser();
        if (!$user || $user->getUserType() !== 1) {
            return $this->json(['error' => 'Accès refusé'], Response::HTTP_FORBIDDEN);
        }

        $search = $request->query->get('search', '');

        if (empty(trim($search))) {
            $tags = $tagRepository->findAllOrderedByName();
        } else {
            $tags = $tagRepository->findByName($search);
        }

        $data = array_map(function($tag) {
            return [
                'id' => $tag->getId(),
                'name' => $tag->getName(),
            ];
        }, $tags);

        return $this->json(['tags' => $data]);
    }

    /**
     * Create a new tag
     */
    #[Route('/tags', name: 'api_admin_tags_create', methods: ['POST'])]
    public function createTag(
        Request $request,
        TagRepository $tagRepository,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        /** @var \App\Entity\User|null $user */
        $user = $this->getUser();
        if (!$user || $user->getUserType() !== 1) {
            return $this->json(['error' => 'Accès refusé'], Response::HTTP_FORBIDDEN);
        }

        $data = json_decode($request->getContent(), true);

        if (!isset($data['name']) || empty(trim($data['name']))) {
            return $this->json(['error' => 'Le nom du tag est requis'], Response::HTTP_BAD_REQUEST);
        }

        $name = trim($data['name']);

        // Check if tag already exists
        $existingTag = $tagRepository->findOneBy(['name' => $name]);
        if ($existingTag) {
            return $this->json(['error' => 'Un tag avec ce nom existe déjà'], Response::HTTP_CONFLICT);
        }

        $tag = new Tag();
        $tag->setName($name);

        $entityManager->persist($tag);
        $entityManager->flush();

        return $this->json([
            'message' => 'Tag créé avec succès',
            'tag' => [
                'id' => $tag->getId(),
                'name' => $tag->getName(),
            ]
        ], Response::HTTP_CREATED);
    }

    /**
     * Update a tag
     */
    #[Route('/tags/{id}', name: 'api_admin_tags_update', methods: ['PUT', 'PATCH'])]
    public function updateTag(
        int $id,
        Request $request,
        TagRepository $tagRepository,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        /** @var \App\Entity\User|null $user */
        $user = $this->getUser();
        if (!$user || $user->getUserType() !== 1) {
            return $this->json(['error' => 'Accès refusé'], Response::HTTP_FORBIDDEN);
        }

        $tag = $tagRepository->find($id);
        if (!$tag) {
            return $this->json(['error' => 'Tag non trouvé'], Response::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true);

        if (!isset($data['name']) || empty(trim($data['name']))) {
            return $this->json(['error' => 'Le nom du tag est requis'], Response::HTTP_BAD_REQUEST);
        }

        $name = trim($data['name']);

        // Check if another tag with this name already exists
        $existingTag = $tagRepository->findOneBy(['name' => $name]);
        if ($existingTag && $existingTag->getId() !== $tag->getId()) {
            return $this->json(['error' => 'Un tag avec ce nom existe déjà'], Response::HTTP_CONFLICT);
        }

        $tag->setName($name);
        $entityManager->flush();

        return $this->json([
            'message' => 'Tag modifié avec succès',
            'tag' => [
                'id' => $tag->getId(),
                'name' => $tag->getName(),
            ]
        ]);
    }

    /**
     * Delete a tag
     */
    #[Route('/tags/{id}', name: 'api_admin_tags_delete', methods: ['DELETE'])]
    public function deleteTag(
        int $id,
        TagRepository $tagRepository,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        /** @var \App\Entity\User|null $user */
        $user = $this->getUser();
        if (!$user || $user->getUserType() !== 1) {
            return $this->json(['error' => 'Accès refusé'], Response::HTTP_FORBIDDEN);
        }

        $tag = $tagRepository->find($id);
        if (!$tag) {
            return $this->json(['error' => 'Tag non trouvé'], Response::HTTP_NOT_FOUND);
        }

        $entityManager->remove($tag);
        $entityManager->flush();

        return $this->json(['message' => 'Tag supprimé avec succès']);
    }

    /**
     * Get all banned users
     */
    #[Route('/banned-users', name: 'api_admin_banned_users_list', methods: ['GET'])]
    public function getBannedUsers(UserRepository $userRepository): JsonResponse
    {
        /** @var \App\Entity\User|null $user */
        $user = $this->getUser();
        if (!$user || $user->getUserType() !== 1) {
            return $this->json(['error' => 'Accès refusé'], Response::HTTP_FORBIDDEN);
        }

        $bannedUsers = $userRepository->findBy(['isBanned' => true]);

        $data = array_map(function($bannedUser) {
            return [
                'id' => $bannedUser->getId(),
                'username' => $bannedUser->getUsername(),
                'firstName' => $bannedUser->getFirstName(),
                'lastName' => $bannedUser->getLastName(),
                'email' => $bannedUser->getEmail(),
                'profileImage' => $bannedUser->getProfileImage() ? '/profile_images/' . $bannedUser->getProfileImage() : null,
                'affiliationLocation' => $bannedUser->getAffiliationLocation(),
                'specialization' => $bannedUser->getSpecialization(),
            ];
        }, $bannedUsers);

        return $this->json(['bannedUsers' => $data]);
    }

    /**
     * Search users (for admin ban feature)
     */
    #[Route('/search-users', name: 'api_admin_search_users', methods: ['GET'])]
    public function searchUsers(Request $request, UserRepository $userRepository): JsonResponse
    {
        /** @var \App\Entity\User|null $user */
        $user = $this->getUser();
        if (!$user || $user->getUserType() !== 1) {
            return $this->json(['error' => 'Accès refusé'], Response::HTTP_FORBIDDEN);
        }

        $query = trim($request->query->get('q', ''));
        
        if (empty($query)) {
            return $this->json(['users' => []]);
        }

        // Search by username, email, first name or last name
        $searchResults = $userRepository->createQueryBuilder('u')
            ->where('u.username LIKE :query')
            ->orWhere('u.email LIKE :query')
            ->orWhere('u.firstName LIKE :query')
            ->orWhere('u.lastName LIKE :query')
            ->setParameter('query', '%' . $query . '%')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult();

        $data = array_map(function($searchUser) {
            return [
                'id' => $searchUser->getId(),
                'username' => $searchUser->getUsername(),
                'firstName' => $searchUser->getFirstName(),
                'lastName' => $searchUser->getLastName(),
                'email' => $searchUser->getEmail(),
                'profileImage' => $searchUser->getProfileImage() ? '/profile_images/' . $searchUser->getProfileImage() : null,
                'affiliationLocation' => $searchUser->getAffiliationLocation(),
                'specialization' => $searchUser->getSpecialization(),
            ];
        }, $searchResults);

        return $this->json(['users' => $data]);
    }

    /**
     * Ban a user
     */
    #[Route('/users/{id}/ban', name: 'api_admin_ban_user', methods: ['POST'])]
    public function banUser(
        int $id,
        UserRepository $userRepository,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        /** @var \App\Entity\User|null $user */
        $user = $this->getUser();
        if (!$user || $user->getUserType() !== 1) {
            return $this->json(['error' => 'Accès refusé'], Response::HTTP_FORBIDDEN);
        }

        // Cannot ban yourself
        if ($user->getId() === $id) {
            return $this->json(['error' => 'Vous ne pouvez pas vous bannir vous-même'], Response::HTTP_BAD_REQUEST);
        }

        // Cannot ban another admin
        $targetUser = $userRepository->find($id);
        if (!$targetUser) {
            return $this->json(['error' => 'Utilisateur non trouvé'], Response::HTTP_NOT_FOUND);
        }

        if ($targetUser->getUserType() === 1) {
            return $this->json(['error' => 'Vous ne pouvez pas bannir un administrateur'], Response::HTTP_BAD_REQUEST);
        }

        if ($targetUser->isBanned()) {
            return $this->json(['error' => 'Cet utilisateur est déjà banni'], Response::HTTP_CONFLICT);
        }

        $targetUser->setIsBanned(true);
        $entityManager->flush();

        return $this->json([
            'message' => 'Utilisateur banni avec succès',
            'user' => [
                'id' => $targetUser->getId(),
                'username' => $targetUser->getUsername(),
            ]
        ]);
    }

    /**
     * Unban a user
     */
    #[Route('/users/{id}/unban', name: 'api_admin_unban_user', methods: ['POST'])]
    public function unbanUser(
        int $id,
        UserRepository $userRepository,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        /** @var \App\Entity\User|null $user */
        $user = $this->getUser();
        if (!$user || $user->getUserType() !== 1) {
            return $this->json(['error' => 'Accès refusé'], Response::HTTP_FORBIDDEN);
        }

        $targetUser = $userRepository->find($id);
        if (!$targetUser) {
            return $this->json(['error' => 'Utilisateur non trouvé'], Response::HTTP_NOT_FOUND);
        }

        if (!$targetUser->isBanned()) {
            return $this->json(['error' => 'Cet utilisateur n\'est pas banni'], Response::HTTP_CONFLICT);
        }

        $targetUser->setIsBanned(false);
        $entityManager->flush();

        return $this->json([
            'message' => 'Utilisateur débanni avec succès',
            'user' => [
                'id' => $targetUser->getId(),
                'username' => $targetUser->getUsername(),
            ]
        ]);
    }
}
