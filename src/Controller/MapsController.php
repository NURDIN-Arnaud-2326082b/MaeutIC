<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Request;
use App\Repository\UserRepository;
use App\Repository\TagRepository;
use App\Service\NetworkService;
use App\Service\OptimizedRecommendationService;

final class MapsController extends AbstractController
{
    #[Route('/maps', name: 'app_maps')]
    public function index(
        UserRepository $userRepository, 
        NetworkService $networkService,
        OptimizedRecommendationService $recommendationService
    ): Response {
        $currentUser = $this->getUser();
        
        $friendIds = [];
        $friends = [];
        $recommendedUsers = [];
        $userScores = [];

        if ($currentUser) {
            $friends = $networkService->getUserNetwork($currentUser);
            $friendIds = array_map(fn($friend) => $friend->getId(), $friends);
            
            $allUsers = $userRepository->findAll();
            $nonFriendUsers = array_filter($allUsers, function($user) use ($currentUser, $friendIds) {
                return $user->getId() !== $currentUser->getId() && !in_array($user->getId(), $friendIds);
            });

            $recommendationService->clearUserCache($currentUser);
            $recommendationScores = $recommendationService->calculateRecommendationScores($currentUser, 1000);
            
            foreach ($recommendationScores as $userId => $scoreData) {
                $userScores[$userId] = $scoreData['score'];
            }
            
            $topRecommendedUsers = array_slice($recommendationScores, 0, 40, true);
            $recommendedUsers = array_map(fn($scoreData) => $scoreData['user'], $topRecommendedUsers);
            
            if (count($recommendedUsers) < 40) {
                $usedIds = array_merge($friendIds, [$currentUser->getId()], array_map(fn($u) => $u->getId(), $recommendedUsers));
                $availableUsers = array_filter($nonFriendUsers, function($user) use ($usedIds) {
                    return !in_array($user->getId(), $usedIds);
                });
                
                $needed = 40 - count($recommendedUsers);
                $randomUsers = array_slice($availableUsers, 0, $needed);
                $recommendedUsers = array_merge($recommendedUsers, $randomUsers);
                
                foreach ($randomUsers as $randomUser) {
                    if (!isset($userScores[$randomUser->getId()])) {
                        $userScores[$randomUser->getId()] = 0.05;
                    }
                }
            }
            
            $this->displayScoreDetails($recommendationScores);
        }

        $usersToDisplay = [];
        if ($currentUser) {
            $usersToDisplay[] = $currentUser;
            $usersToDisplay = array_merge($usersToDisplay, $friends);
            $usersToDisplay = array_merge($usersToDisplay, $recommendedUsers);
        } else {
            $usersToDisplay = $userRepository->findAll();
        }

        return $this->render('maps/index.html.twig', [
            'controller_name' => 'MapsController',
            'users' => $usersToDisplay,
            'friend_ids' => $friendIds,
            'current_user_id' => $currentUser ? $currentUser->getId() : null,
            'user_scores' => $userScores,
        ]);
    }

    #[Route('/tag/search', name: 'app_tag_search')]
    public function searchTags(Request $request, TagRepository $tagRepository): JsonResponse
    {
        $query = $request->query->get('q', '');
        
        if (strlen($query) < 2) {
            return $this->json([]);
        }

        // Recherche réelle dans la table tag
        $tags = $tagRepository->createQueryBuilder('t')
            ->where('t.name LIKE :query')
            ->setParameter('query', '%' . $query . '%')
            ->orderBy('t.name', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult();

        $formattedTags = array_map(function($tag) {
            return [
                'id' => $tag->getId(),
                'name' => $tag->getName(),
            ];
        }, $tags);

        return $this->json($formattedTags);
    }

    #[Route('/maps/filter', name: 'app_user_map_filter')]
    public function filter(
        Request $request, 
        UserRepository $userRepository, 
        NetworkService $networkService
    ): Response {
        $currentUser = $this->getUser();
        $tagIds = $request->query->all('tags');
        
        $users = $userRepository->findByTaggableQuestion1Tags($tagIds);
        
        $friendIds = [];
        if ($currentUser) {
            $friends = $networkService->getUserNetwork($currentUser);
            $friendIds = array_map(fn($friend) => $friend->getId(), $friends);
        }

        return $this->render('maps/_bubbles.html.twig', [
            'users' => $users,
            'friend_ids' => $friendIds,
            'current_user_id' => $currentUser ? $currentUser->getId() : null,
        ]);
    }

    #[Route('/maps/filter-by-type', name: 'app_maps_filter_by_type')]
    public function filterByType(
        Request $request,
        UserRepository $userRepository, 
        NetworkService $networkService,
        OptimizedRecommendationService $recommendationService
    ): Response {
        $currentUser = $this->getUser();
        $showFriends = $request->query->get('friends', 'true') === 'true';
        $showRecommendations = $request->query->get('recommendations', 'true') === 'true';
        
        $friendIds = [];
        $friends = [];
        $recommendedUsers = [];
        $userScores = [];

        if ($currentUser) {
            $friends = $networkService->getUserNetwork($currentUser);
            $friendIds = array_map(fn($friend) => $friend->getId(), $friends);
            
            if ($showRecommendations) {
                $allUsers = $userRepository->findAll();
                $nonFriendUsers = array_filter($allUsers, function($user) use ($currentUser, $friendIds) {
                    return $user->getId() !== $currentUser->getId() && !in_array($user->getId(), $friendIds);
                });

                $recommendationService->clearUserCache($currentUser);
                $recommendationScores = $recommendationService->calculateRecommendationScores($currentUser, 1000);
                
                foreach ($recommendationScores as $userId => $scoreData) {
                    $userScores[$userId] = $scoreData['score'];
                }
                
                $topRecommendedUsers = array_slice($recommendationScores, 0, 40, true);
                $recommendedUsers = array_map(fn($scoreData) => $scoreData['user'], $topRecommendedUsers);
                
                if (count($recommendedUsers) < 40) {
                    $usedIds = array_merge($friendIds, [$currentUser->getId()], array_map(fn($u) => $u->getId(), $recommendedUsers));
                    $availableUsers = array_filter($nonFriendUsers, function($user) use ($usedIds) {
                        return !in_array($user->getId(), $usedIds);
                    });
                    
                    $needed = 40 - count($recommendedUsers);
                    $randomUsers = array_slice($availableUsers, 0, $needed);
                    $recommendedUsers = array_merge($recommendedUsers, $randomUsers);
                    
                    foreach ($randomUsers as $randomUser) {
                        if (!isset($userScores[$randomUser->getId()])) {
                            $userScores[$randomUser->getId()] = 0.05;
                        }
                    }
                }
            }
        }

        $usersToDisplay = [];
        
        if ($currentUser) {
            $usersToDisplay[] = $currentUser;
            
            if ($showFriends) {
                $usersToDisplay = array_merge($usersToDisplay, $friends);
            }
            
            if ($showRecommendations) {
                $usersToDisplay = array_merge($usersToDisplay, $recommendedUsers);
            }
        } else {
            $usersToDisplay = $userRepository->findAll();
        }

        return $this->render('maps/_bubbles.html.twig', [
            'users' => $usersToDisplay,
            'friend_ids' => $friendIds,
            'current_user_id' => $currentUser ? $currentUser->getId() : null,
            'user_scores' => $userScores,
        ]);
    }

    #[Route('/maps/filter-by-tags', name: 'app_maps_filter_by_tags')]
    public function filterByTags(
        Request $request,
        UserRepository $userRepository, 
        NetworkService $networkService,
        OptimizedRecommendationService $recommendationService
    ): Response {
        $currentUser = $this->getUser();
        $tagIds = $request->query->all('tags');
        $showFriends = $request->query->get('friends', 'true') === 'true';
        $showRecommendations = $request->query->get('recommendations', 'true') === 'true';
        $page = $request->query->getInt('page', 1);
        $limit = $request->query->getInt('limit', 20);
        
        $friendIds = [];
        $friends = [];
        $userScores = [];
        $totalUsers = 0;

        if ($currentUser) {
            $friends = $networkService->getUserNetwork($currentUser);
            $friendIds = array_map(fn($friend) => $friend->getId(), $friends);
        }

        $taggedUsers = $userRepository->findByTaggableQuestion1Tags($tagIds);
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
                $friendsInResults = array_filter($paginatedUsers, function($user) use ($friendIds) {
                    return in_array($user->getId(), $friendIds);
                });
                $usersToDisplay = array_merge($usersToDisplay, $friendsInResults);
            }
            
            if ($showRecommendations) {
                $otherUsers = array_filter($paginatedUsers, function($user) use ($currentUser, $friendIds) {
                    return $user->getId() !== $currentUser->getId() && !in_array($user->getId(), $friendIds);
                });
                $usersToDisplay = array_merge($usersToDisplay, $otherUsers);
                
                $recommendationScores = $recommendationService->calculateRecommendationScores($currentUser, 1000);
                foreach ($recommendationScores as $userId => $scoreData) {
                    $userScores[$userId] = $scoreData['score'];
                }
            }
        } else {
            $usersToDisplay = $paginatedUsers;
        }

        $usersToDisplay = array_unique($usersToDisplay, SORT_REGULAR);

        $response = $this->render('maps/_bubbles.html.twig', [
            'users' => $usersToDisplay,
            'friend_ids' => $friendIds,
            'current_user_id' => $currentUser ? $currentUser->getId() : null,
            'user_scores' => $userScores,
        ]);

        $response->headers->set('X-Total-Users', $totalUsers);
        $response->headers->set('X-Total-Pages', $totalPages);
        $response->headers->set('X-Current-Page', $page);

        return $response;
    }

    private function displayScoreDetails(array $recommendationScores): void
    {
        echo "<script>";
        echo "console.log('=== DÉTAILS DES SCORES DE RECOMMANDATION AVEC IA ===');";
        
        $counter = 0;
        foreach ($recommendationScores as $userId => $scoreData) {
            $user = $scoreData['user'];
            $details = $scoreData['details'];
            
            echo "console.log('Utilisateur: {$user->getFirstName()} {$user->getLastName()} (ID: {$user->getId()})');";
            echo "console.log('Score total: {$scoreData['score']}');";
            echo "console.log('  - IA Comportementale: " . ($details['behavioral']['score'] ?? 0) . "');";
            echo "console.log('  - Spécialisation: " . ($details['specialization']['score'] ?? 0) . "');";
            echo "console.log('  - Sujet de recherche: " . ($details['research_topic']['score'] ?? 0) . "');";
            echo "console.log('  - Localisation: " . ($details['affiliation_location']['score'] ?? 0) . "');";
            echo "console.log('  - Questions taggables: " . ($details['taggable_questions']['score'] ?? 0) . "');";
            if (isset($details['diversity_boost'])) {
                echo "console.log('  - Boost Diversité: " . $details['diversity_boost'] . "');";
            }
            echo "console.log('---');";
            
            $counter++;
            if ($counter >= 8) {
                break;
            }
        }
        
        echo "console.log('Total des recommandations calculées: " . count($recommendationScores) . "');";
        echo "console.log('================================');";
        echo "</script>";
    }
}