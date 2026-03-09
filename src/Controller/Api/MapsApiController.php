<?php

namespace App\Controller\Api;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\NetworkService;
use App\Service\OptimizedRecommendationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
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
     * Search tags for autocomplete
     */
    #[Route('/tags/search', name: 'api_maps_tags_search', methods: ['GET'])]
    public function searchTags(
        Request $request,
        \App\Repository\TagRepository $tagRepository
    ): JsonResponse {
        $query = $request->query->get('q', '');

        if (strlen($query) < 2) {
            return new JsonResponse([]);
        }

        $tags = $tagRepository->createQueryBuilder('t')
            ->where('t.name LIKE :query')
            ->setParameter('query', '%' . $query . '%')
            ->orderBy('t.name', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult();

        $formattedTags = array_map(function ($tag) {
            return [
                'id' => $tag->getId(),
                'name' => $tag->getName(),
            ];
        }, $tags);

        return new JsonResponse($formattedTags);
    }

    /**
     * Search users by query with pagination
     */
    #[Route('/search-users', name: 'api_maps_search_users', methods: ['GET'])]
    public function searchUsers(
        Request $request,
        UserRepository $userRepository,
        NetworkService $networkService,
        OptimizedRecommendationService $recommendationService
    ): JsonResponse {
        /** @var User|null $currentUser */
        $currentUser = $this->getUser();
        $searchQuery = $request->query->get('q', '');
        $showFriends = $request->query->get('friends', 'true') === 'true';
        $showRecommendations = $request->query->get('recommendations', 'true') === 'true';
        $page = $request->query->getInt('page', 1);
        $limit = min($request->query->getInt('limit', 20), 100);

        $friendIds = [];
        $friends = [];
        $userScores = [];

        if ($currentUser) {
            $friends = $networkService->getUserNetwork($currentUser);
            $friendIds = array_map(fn($friend) => $friend->getId(), $friends);
        }

        $searchedUsers = $userRepository->findBySearchQuery($searchQuery, 200);
        $totalUsers = count($searchedUsers);

        $offset = ($page - 1) * $limit;
        $paginatedUsers = array_slice($searchedUsers, $offset, $limit);
        $totalPages = ceil($totalUsers / $limit);

        $usersToDisplay = [];

        if ($currentUser) {
            $currentUserInResults = array_filter($paginatedUsers, fn($user) => $user->getId() === $currentUser->getId());
            if (count($currentUserInResults) > 0) {
                $usersToDisplay[] = $currentUser;
            }

            if ($showFriends) {
                $friendsInResults = array_filter($paginatedUsers, function ($user) use ($friendIds) {
                    return in_array($user->getId(), $friendIds);
                });
                $usersToDisplay = array_merge($usersToDisplay, $friendsInResults);
            }

            if ($showRecommendations) {
                $otherUsers = array_filter($paginatedUsers, function ($user) use ($currentUser, $friendIds) {
                    return $user->getId() !== $currentUser->getId() && !in_array($user->getId(), $friendIds);
                });
                $usersToDisplay = array_merge($usersToDisplay, $otherUsers);

                if (!empty($otherUsers)) {
                    $recommendationScores = $recommendationService->calculateRecommendationScores($currentUser, 30);
                    foreach ($recommendationScores as $userId => $scoreData) {
                        $userScores[$userId] = $scoreData['score'];
                    }
                }
            }
        } else {
            $usersToDisplay = $paginatedUsers;
        }

        $usersToDisplay = array_unique($usersToDisplay, SORT_REGULAR);
        if ($currentUser) {
            $usersToDisplay = $this->filterOutBlockedUsers($usersToDisplay, $currentUser);
            $friendIds = array_values(array_filter($friendIds, fn($id) => !$currentUser->isBlocked($id) && !$currentUser->isBlockedBy($id)));
        }

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
            'totalUsers' => $totalUsers,
            'totalPages' => $totalPages,
            'currentPage' => $page,
        ]);
    }

    /**
     * Filter users by tags with pagination
     */
    #[Route('/filter-by-tags', name: 'api_maps_filter_by_tags', methods: ['GET'])]
    public function filterByTags(
        Request $request,
        UserRepository $userRepository,
        NetworkService $networkService,
        OptimizedRecommendationService $recommendationService
    ): JsonResponse {
        /** @var User|null $currentUser */
        $currentUser = $this->getUser();
        
        // Get tags array - Symfony automatically parses tags[] into an array
        $allParams = $request->query->all();
        $tagIds = $allParams['tags'] ?? [];
        
        // Convert to array of integers
        $tagIds = array_map('intval', is_array($tagIds) ? $tagIds : []);
        
        // If no tags, return empty result
        if (empty($tagIds)) {
            return new JsonResponse([
                'users' => [],
                'friendIds' => [],
                'currentUserId' => $currentUser ? $currentUser->getId() : null,
                'userScores' => [],
                'totalUsers' => 0,
                'totalPages' => 0,
                'currentPage' => 1,
            ]);
        }
        
        $showFriends = $request->query->get('friends', 'true') === 'true';
        $showRecommendations = $request->query->get('recommendations', 'true') === 'true';
        $page = $request->query->getInt('page', 1);
        $limit = min($request->query->getInt('limit', 20), 100);

        $friendIds = [];
        $friends = [];
        $userScores = [];
        $totalUsers = 0;

        if ($currentUser) {
            $friends = $networkService->getUserNetwork($currentUser);
            $friendIds = array_map(fn($friend) => $friend->getId(), $friends);
        }

        $taggedUsers = $userRepository->findByTaggableQuestion1Tags($tagIds, 200);
        $totalUsers = count($taggedUsers);

        $offset = ($page - 1) * $limit;
        $paginatedUsers = array_slice($taggedUsers, $offset, $limit);
        $totalPages = ceil($totalUsers / $limit);

        $usersToDisplay = [];

        if ($currentUser) {
            $currentUserInResults = array_filter($paginatedUsers, fn($user) => $user->getId() === $currentUser->getId());
            if (count($currentUserInResults) > 0) {
                $usersToDisplay[] = $currentUser;
            }

            if ($showFriends) {
                $friendsInResults = array_filter($paginatedUsers, function ($user) use ($friendIds) {
                    return in_array($user->getId(), $friendIds);
                });
                $usersToDisplay = array_merge($usersToDisplay, $friendsInResults);
            }

            if ($showRecommendations) {
                $otherUsers = array_filter($paginatedUsers, function ($user) use ($currentUser, $friendIds) {
                    return $user->getId() !== $currentUser->getId() && !in_array($user->getId(), $friendIds);
                });
                $usersToDisplay = array_merge($usersToDisplay, $otherUsers);

                if (!empty($otherUsers)) {
                    $recommendationScores = $recommendationService->calculateRecommendationScores($currentUser, 30);
                    foreach ($recommendationScores as $userId => $scoreData) {
                        $userScores[$userId] = $scoreData['score'];
                    }
                }
            }
        } else {
            $usersToDisplay = $paginatedUsers;
        }

        $usersToDisplay = array_unique($usersToDisplay, SORT_REGULAR);
        if ($currentUser) {
            $usersToDisplay = $this->filterOutBlockedUsers($usersToDisplay, $currentUser);
            $friendIds = array_values(array_filter($friendIds, fn($id) => !$currentUser->isBlocked($id) && !$currentUser->isBlockedBy($id)));
        }

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
            'totalUsers' => $totalUsers,
            'totalPages' => $totalPages,
            'currentPage' => $page,
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
