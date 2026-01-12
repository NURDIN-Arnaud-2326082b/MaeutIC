<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\TagRepository;
use App\Repository\UserRepository;
use App\Service\NetworkService;
use App\Service\OptimizedRecommendationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class MapsController extends AbstractController
{
    private const MAX_USERS_DISPLAY = 100;
    private const USERS_PER_PAGE = 20;
    private const MAX_RECOMMENDATIONS = 50;
    private const MAX_SEARCH_RESULTS = 200;

    /**
     * Affiche la carte des utilisateurs avec réseau et recommandations
     *
     * Pour un utilisateur connecté :
     * - Affiche son réseau d'amis
     * - Calcule et affiche les utilisateurs recommandés selon les affinités
     * - Filtre les utilisateurs bloqués
     *
     * @param UserRepository $userRepository Repository des utilisateurs
     * @param NetworkService $networkService Service de gestion du réseau social
     * @param OptimizedRecommendationService $recommendationService Service de recommandations optimisé
     * @return Response Page de la carte avec utilisateurs et scores
     */
    #[Route('/maps', name: 'app_maps')]
    public function index(
        UserRepository                 $userRepository,
        NetworkService                 $networkService,
        OptimizedRecommendationService $recommendationService
    ): Response
    {
        $currentUser = $this->getUser();

        $friendIds = [];
        $friends = [];
        $recommendedUsers = [];
        $userScores = [];

        if ($currentUser) {
            $friends = $networkService->getUserNetwork($currentUser);
            $friendIds = array_map(fn($friend) => $friend->getId(), $friends);
            // retirer des friends les users bloqués / qui nous ont bloqué
            $friendIds = array_values(array_filter($friendIds, fn($id) => !$currentUser->isBlocked($id) && !$currentUser->isBlockedBy($id)));
            // filtrer la liste d'amis retournée aussi
            $friends = array_values(array_filter($friends, fn($f) => !$currentUser->isBlocked($f->getId()) && !$currentUser->isBlockedBy($f->getId())));

            // ✅ OPTIMISÉ: Récupère seulement les utilisateurs nécessaires
            $nonFriendUsers = $userRepository->findNonFriendUsers($currentUser, $friendIds, 200);

            // ✅ OPTIMISÉ: Limite le nombre de calculs
            $recommendationScores = $recommendationService->calculateRecommendationScores($currentUser, self::MAX_RECOMMENDATIONS);

            foreach ($recommendationScores as $userId => $scoreData) {
                $userScores[$userId] = $scoreData['score'];
            }

            $topRecommendedUsers = array_slice($recommendationScores, 0, 40, true);
            $recommendedUsers = array_map(fn($scoreData) => $scoreData['user'], $topRecommendedUsers);

            // filtrer les recommendedUsers aussi
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

            $this->displayScoreDetails($recommendationScores);
        }

        $usersToDisplay = [];
        if ($currentUser) {
            $usersToDisplay[] = $currentUser;
            $usersToDisplay = array_merge($usersToDisplay, $friends);
            $usersToDisplay = array_merge($usersToDisplay, $recommendedUsers);

            // ✅ LIMITE STRICTE
            $usersToDisplay = array_slice($usersToDisplay, 0, self::MAX_USERS_DISPLAY);
            // Retirer tous les users bloqués / qui nous ont bloqué
            $usersToDisplay = $this->filterOutBlockedUsers($usersToDisplay, $currentUser);
        } else {
            // ✅ OPTIMISÉ: Limite pour les utilisateurs non connectés
            $usersToDisplay = $userRepository->findPaginated(1, self::MAX_USERS_DISPLAY);
        }

        return $this->render('maps/index.html.twig', [
            'controller_name' => 'MapsController',
            'users' => $usersToDisplay,
            'friend_ids' => $friendIds,
            'current_user_id' => $currentUser?->getId(),
            'user_scores' => $userScores,
        ]);
    }

    /**
     * Affiche les détails des scores de recommandation dans la console du navigateur
     * en mode développement pour le débogage
     *
     * @param array $recommendationScores Scores de recommandation calculés
     * @return void
     */
    private function displayScoreDetails(array $recommendationScores): void
    {
        // ✅ OPTIMISÉ: Log seulement en mode debug
        if ($_ENV['APP_ENV'] === 'dev') {
            echo "<script>";
            echo "console.log('=== DÉTAILS DES SCORES DE RECOMMANDATION AVEC IA ===');";

            $counter = 0;
            foreach ($recommendationScores as $userId => $scoreData) {
                if ($counter >= 5) break; // ✅ LIMITE les logs

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
            }

            echo "console.log('Total des recommandations calculées: " . count($recommendationScores) . "');";
            echo "console.log('================================');";
            echo "</script>";
        }
    }

    /**
     * Retire tous les utilisateurs bloqués ou qui ont bloqué l'utilisateur courant
     *
     * @param array $users Liste des utilisateurs à filtrer
     * @param User|null $currentUser Utilisateur courant
     * @return array Liste des utilisateurs filtrés
     */
    private function filterOutBlockedUsers(array $users, ?User $currentUser): array
    {
        if (!$currentUser) return $users;
        $out = [];
        foreach ($users as $u) {
            if (!($u instanceof User)) continue;
            $id = $u->getId();
            if ($id === $currentUser->getId()) {
                $out[] = $u;
                continue;
            }
            if ($currentUser->isBlocked($id)) continue;
            if ($currentUser->isBlockedBy($id)) continue;
            $out[] = $u;
        }
        // unique and preserve order
        return array_values(array_unique($out, SORT_REGULAR));
    }

    /**
     * Recherche des tags pour l'autocomplétion
     *
     * @param Request $request Requête HTTP
     * @param TagRepository $tagRepository Repository des tags
     * @return JsonResponse Liste des tags correspondants au format JSON
     */
    #[Route('/tag/search', name: 'app_tag_search')]
    public function searchTags(Request $request, TagRepository $tagRepository): JsonResponse
    {
        $query = $request->query->get('q', '');

        if (strlen($query) < 2) {
            return $this->json([]);
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

        return $this->json($formattedTags);
    }

    /**
     * Recherche des utilisateurs par nom avec pagination
     *
     * @param Request $request Requête HTTP
     * @param UserRepository $userRepository Repository des utilisateurs
     * @param NetworkService $networkService Service de gestion du réseau
     * @param OptimizedRecommendationService $recommendationService Service de recommandations
     * @return Response Liste des utilisateurs correspondants avec en-têtes de pagination
     */
    #[Route('/maps/search-users', name: 'app_maps_search_users')]
    public function searchUsers(
        Request                        $request,
        UserRepository                 $userRepository,
        NetworkService                 $networkService,
        OptimizedRecommendationService $recommendationService
    ): Response
    {
        $currentUser = $this->getUser();
        $searchQuery = $request->query->get('q', '');
        $showFriends = $request->query->get('friends', 'true') === 'true';
        $showRecommendations = $request->query->get('recommendations', 'true') === 'true';
        $page = $request->query->getInt('page', 1);
        $limit = min($request->query->getInt('limit', self::USERS_PER_PAGE), 100);

        $friendIds = [];
        $friends = [];
        $userScores = [];

        if ($currentUser) {
            $friends = $networkService->getUserNetwork($currentUser);
            $friendIds = array_map(fn($friend) => $friend->getId(), $friends);
        }

        // ✅ OPTIMISÉ: Limite intégrée dans la requête
        $searchedUsers = $userRepository->findBySearchQuery($searchQuery, self::MAX_SEARCH_RESULTS);
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
                    // ✅ OPTIMISÉ: Calcul seulement si nécessaire avec limite
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

        $response = $this->render('maps/_bubbles.html.twig', [
            'users' => $usersToDisplay,
            'friend_ids' => $friendIds,
            'current_user_id' => $currentUser?->getId(),
            'user_scores' => $userScores,
        ]);

        $response->headers->set('X-Total-Users', $totalUsers);
        $response->headers->set('X-Total-Pages', $totalPages);
        $response->headers->set('X-Current-Page', $page);

        return $response;
    }

    /**
     * Filtre les utilisateurs affichés sur la carte par type (amis, recommandations)
     *
     * @param Request $request Requête HTTP
     * @param UserRepository $userRepository Repository des utilisateurs
     * @param NetworkService $networkService Service de gestion du réseau
     * @param OptimizedRecommendationService $recommendationService Service de recommandations
     * @return Response Liste des utilisateurs filtrés
     */
    #[Route('/maps/filter-by-type', name: 'app_maps_filter_by_type')]
    public function filterByType(
        Request                        $request,
        UserRepository                 $userRepository,
        NetworkService                 $networkService,
        OptimizedRecommendationService $recommendationService
    ): Response
    {
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
                // ✅ OPTIMISÉ: Récupère seulement les utilisateurs nécessaires
                $nonFriendUsers = $userRepository->findNonFriendUsers($currentUser, $friendIds, 150);

                // ✅ OPTIMISÉ: Limite le nombre de calculs
                $recommendationScores = $recommendationService->calculateRecommendationScores($currentUser, self::MAX_RECOMMENDATIONS);

                foreach ($recommendationScores as $userId => $scoreData) {
                    $userScores[$userId] = $scoreData['score'];
                }

                $topRecommendedUsers = array_slice($recommendationScores, 0, 40, true);
                $recommendedUsers = array_map(fn($scoreData) => $scoreData['user'], $topRecommendedUsers);

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

            // ✅ LIMITE STRICTE
            $usersToDisplay = array_slice($usersToDisplay, 0, self::MAX_USERS_DISPLAY);
            if ($currentUser) {
                $usersToDisplay = $this->filterOutBlockedUsers($usersToDisplay, $currentUser);
                $friendIds = array_values(array_filter($friendIds, fn($id) => !$currentUser->isBlocked($id) && !$currentUser->isBlockedBy($id)));
            }
        } else {
            // ✅ OPTIMISÉ: Limite pour les utilisateurs non connectés
            $usersToDisplay = $userRepository->findPaginated(1, self::MAX_USERS_DISPLAY);
        }

        return $this->render('maps/_bubbles.html.twig', [
            'users' => $usersToDisplay,
            'friend_ids' => $friendIds,
            'current_user_id' => $currentUser?->getId(),
            'user_scores' => $userScores,
        ]);
    }

    // Helper: retire tous les users de la liste que $currentUser a bloqués ou qui l'ont bloqué

    /**
     * Filtre les utilisateurs par tags avec pagination
     *
     * @param Request $request Requête HTTP
     * @param UserRepository $userRepository Repository des utilisateurs
     * @param NetworkService $networkService Service de gestion du réseau
     * @param OptimizedRecommendationService $recommendationService Service de recommandations
     * @return Response Liste des utilisateurs filtrés avec en-têtes de pagination
     */
    #[Route('/maps/filter-by-tags', name: 'app_maps_filter_by_tags')]
    public function filterByTags(
        Request                        $request,
        UserRepository                 $userRepository,
        NetworkService                 $networkService,
        OptimizedRecommendationService $recommendationService
    ): Response
    {
        $currentUser = $this->getUser();
        $tagIds = $request->query->all('tags');
        $showFriends = $request->query->get('friends', 'true') === 'true';
        $showRecommendations = $request->query->get('recommendations', 'true') === 'true';
        $page = $request->query->getInt('page', 1);
        $limit = min($request->query->getInt('limit', self::USERS_PER_PAGE), 100);

        $friendIds = [];
        $friends = [];
        $userScores = [];
        $totalUsers = 0;

        if ($currentUser) {
            $friends = $networkService->getUserNetwork($currentUser);
            $friendIds = array_map(fn($friend) => $friend->getId(), $friends);
        }

        // ✅ OPTIMISÉ: Limite intégrée dans la requête
        $taggedUsers = $userRepository->findByTaggableQuestion1Tags($tagIds, self::MAX_SEARCH_RESULTS);
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

                // ✅ OPTIMISÉ: Calcul seulement si nécessaire avec limite
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

        $response = $this->render('maps/_bubbles.html.twig', [
            'users' => $usersToDisplay,
            'friend_ids' => $friendIds,
            'current_user_id' => $currentUser?->getId(),
            'user_scores' => $userScores,
        ]);

        $response->headers->set('X-Total-Users', $totalUsers);
        $response->headers->set('X-Total-Pages', $totalPages);
        $response->headers->set('X-Current-Page', $page);

        return $response;
    }
}