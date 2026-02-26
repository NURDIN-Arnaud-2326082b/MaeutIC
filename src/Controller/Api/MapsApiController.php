<?php

namespace App\Controller\Api;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\NetworkService;
use App\Service\OptimizedRecommendationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/maps')]
class MapsApiController extends AbstractController
{
    private const MAX_USERS_DISPLAY = 100;
    private const MAX_RECOMMENDATIONS = 50;

    /**
     * Get users for the interactive map
     */
    #[Route('/users', name: 'api_maps_users', methods: ['GET'])]
    public function getUsers(
        UserRepository $userRepository,
        NetworkService $networkService,
        OptimizedRecommendationService $recommendationService
    ): JsonResponse {
        /** @var User|null $currentUser */
        $currentUser = $this->getUser();

        $friendIds = [];
        $friends = [];
        $recommendedUsers = [];
        $userScores = [];

        if ($currentUser) {
            $friends = $networkService->getUserNetwork($currentUser);
            $friendIds = array_map(fn($friend) => $friend->getId(), $friends);
            
            // Filter out blocked users
            $friendIds = array_values(array_filter($friendIds, fn($id) => !$currentUser->isBlocked($id) && !$currentUser->isBlockedBy($id)));
            $friends = array_values(array_filter($friends, fn($f) => !$currentUser->isBlocked($f->getId()) && !$currentUser->isBlockedBy($f->getId())));

            // Get non-friend users
            $nonFriendUsers = $userRepository->findNonFriendUsers($currentUser, $friendIds, 200);

            // Calculate recommendation scores
            $recommendationScores = $recommendationService->calculateRecommendationScores($currentUser, self::MAX_RECOMMENDATIONS);

            foreach ($recommendationScores as $userId => $scoreData) {
                $userScores[$userId] = $scoreData['score'];
            }

            $topRecommendedUsers = array_slice($recommendationScores, 0, 40, true);
            $recommendedUsers = array_map(fn($scoreData) => $scoreData['user'], $topRecommendedUsers);

            // Filter recommended users
            $recommendedUsers = array_values(array_filter($recommendedUsers, fn($u) => !$currentUser->isBlocked($u->getId()) && !$currentUser->isBlockedBy($u->getId())));

            if (count($recommendedUsers) < 40 && count($nonFriendUsers) > 0) {
                $usedIds = array_merge($friendIds, [$currentUser->getId()], array_map(fn($u) => $u->getId(), $recommendedUsers));
                $availableUsers = array_filter($nonFriendUsers, function ($user) use ($usedIds) {
                    return !in_array($user->getId(), $usedIds);
                });

                $needed = 40 - count($recommendedUsers);
                $randomUsers = array_slice($availableUsers, 0, min($needed, count($availableUsers)));
                $recommendedUsers = array_merge($recommendedUsers, $randomUsers);

                foreach ($randomUsers as $randomUser) {
                    if (!isset($userScores[$randomUser->getId()])) {
                        $userScores[$randomUser->getId()] = 0.05;
                    }
                }
            }
        }

        $usersToDisplay = [];
        if ($currentUser) {
            $usersToDisplay[] = $currentUser;
            $usersToDisplay = array_merge($usersToDisplay, $friends);
            $usersToDisplay = array_merge($usersToDisplay, $recommendedUsers);

            // Limit users
            $usersToDisplay = array_slice($usersToDisplay, 0, self::MAX_USERS_DISPLAY);
            
            // Filter out blocked users
            $usersToDisplay = $this->filterOutBlockedUsers($usersToDisplay, $currentUser);
        } else {
            // For non-authenticated users
            $usersToDisplay = $userRepository->findPaginated(1, self::MAX_USERS_DISPLAY);
        }

        // Format users for JSON
        $usersData = array_map(function($user) use ($userScores, $currentUser, $friendIds) {
            $score = $userScores[$user->getId()] ?? null;
            
            return [
                'id' => $user->getId(),
                'username' => $user->getUsername(),
                'firstName' => $user->getFirstName(),
                'lastName' => $user->getLastName(),
                'profileImage' => $user->getProfileImage() 
                    ? '/profile_images/' . $user->getProfileImage() 
                    : '/images/default-profile.png',
                'score' => $score,
                'isCurrentUser' => $currentUser && $user->getId() === $currentUser->getId(),
                'isFriend' => in_array($user->getId(), $friendIds),
            ];
        }, $usersToDisplay);

        return new JsonResponse([
            'users' => array_values($usersData),
            'friendIds' => $friendIds,
            'currentUserId' => $currentUser ? $currentUser->getId() : null,
            'userScores' => $userScores,
        ]);
    }

    /**
     * Filter out blocked users
     */
    private function filterOutBlockedUsers(array $users, ?User $currentUser): array
    {
        if (!$currentUser) {
            return $users;
        }

        return array_values(array_filter($users, function ($user) use ($currentUser) {
            return !$currentUser->isBlocked($user->getId()) && !$currentUser->isBlockedBy($user->getId());
        }));
    }
}
