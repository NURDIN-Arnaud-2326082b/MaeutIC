<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Request;
use App\Repository\UserRepository;
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
        
        // Récupérer les IDs des amis si l'utilisateur est connecté
        $friendIds = [];
        $friends = [];
        $recommendedUsers = [];
        $userScores = []; // NOUVEAU : Stocke les scores par utilisateur

        if ($currentUser) {
            // Récupérer les amis (toujours affichés)
            $friends = $networkService->getUserNetwork($currentUser);
            $friendIds = array_map(fn($friend) => $friend->getId(), $friends);
            
            // Récupérer TOUS les utilisateurs sauf l'utilisateur courant et ses amis
            $allUsers = $userRepository->findAll();
            $nonFriendUsers = array_filter($allUsers, function($user) use ($currentUser, $friendIds) {
                return $user->getId() !== $currentUser->getId() && !in_array($user->getId(), $friendIds);
            });

            // VIDER LE CACHE des recommandations pour forcer le recalcul
            $recommendationService->clearUserCache($currentUser);
            
            // Calculer les scores pour tous les non-amis
            $recommendationScores = $recommendationService->calculateRecommendationScores($currentUser, 1000);
            
            // CRÉER UN TABLEAU SIMPLIFIÉ DES SCORES
            foreach ($recommendationScores as $userId => $scoreData) {
                $userScores[$userId] = $scoreData['score'];
            }
            
            // Prendre les 40 meilleurs scores
            $topRecommendedUsers = array_slice($recommendationScores, 0, 40, true);
            $recommendedUsers = array_map(fn($scoreData) => $scoreData['user'], $topRecommendedUsers);
            
            // Si on a moins de 40 recommandations, compléter avec des utilisateurs aléatoires
            if (count($recommendedUsers) < 40) {
                $usedIds = array_merge($friendIds, [$currentUser->getId()], array_map(fn($u) => $u->getId(), $recommendedUsers));
                $availableUsers = array_filter($nonFriendUsers, function($user) use ($usedIds) {
                    return !in_array($user->getId(), $usedIds);
                });
                
                $needed = 40 - count($recommendedUsers);
                $randomUsers = array_slice($availableUsers, 0, $needed);
                $recommendedUsers = array_merge($recommendedUsers, $randomUsers);
                
                // Ajouter des scores de base pour les utilisateurs aléatoires
                foreach ($randomUsers as $randomUser) {
                    if (!isset($userScores[$randomUser->getId()])) {
                        $userScores[$randomUser->getId()] = 0.05; // Score minimum
                    }
                }
            }
            
            // Afficher les détails des scores dans la console
            $this->displayScoreDetails($recommendationScores);
            
            // DEBUG
            echo "<script>console.log('DEBUG: Amis: " . count($friends) . ", Recommandés: " . count($recommendedUsers) . "');</script>";
            echo "<script>console.log('DEBUG SCORES: " . count($userScores) . " scores calculés');</script>";
        }

        // Combiner amis + recommandations pour l'affichage
        $usersToDisplay = [];
        if ($currentUser) {
            // Ajouter l'utilisateur courant (toujours affiché)
            $usersToDisplay[] = $currentUser;
            // Ajouter les amis (toujours affichés)
            $usersToDisplay = array_merge($usersToDisplay, $friends);
            // Ajouter les recommandations
            $usersToDisplay = array_merge($usersToDisplay, $recommendedUsers);
        } else {
            // Si pas connecté, afficher tous les utilisateurs
            $usersToDisplay = $userRepository->findAll();
        }

        return $this->render('maps/index.html.twig', [
            'controller_name' => 'MapsController',
            'users' => $usersToDisplay,
            'friend_ids' => $friendIds,
            'current_user_id' => $currentUser ? $currentUser->getId() : null,
            'user_scores' => $userScores, // NOUVEAU : Tableau simplifié des scores
        ]);
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
        
        // Récupérer les IDs des amis si l'utilisateur est connecté
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
        
        // Récupérer les IDs des amis si l'utilisateur est connecté
        $friendIds = [];
        $friends = [];
        $recommendedUsers = [];
        $userScores = [];

        if ($currentUser) {
            // Récupérer les amis
            $friends = $networkService->getUserNetwork($currentUser);
            $friendIds = array_map(fn($friend) => $friend->getId(), $friends);
            
            // Récupérer les recommandations si demandé
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

        // Construire la liste des utilisateurs à afficher selon les filtres
        $usersToDisplay = [];
        
        if ($currentUser) {
            // Toujours afficher l'utilisateur courant
            $usersToDisplay[] = $currentUser;
            
            // Afficher les amis si demandé
            if ($showFriends) {
                $usersToDisplay = array_merge($usersToDisplay, $friends);
            }
            
            // Afficher les recommandations si demandé
            if ($showRecommendations) {
                $usersToDisplay = array_merge($usersToDisplay, $recommendedUsers);
            }
        } else {
            // Si pas connecté, afficher tous les utilisateurs
            $usersToDisplay = $userRepository->findAll();
        }

        return $this->render('maps/_bubbles.html.twig', [
            'users' => $usersToDisplay,
            'friend_ids' => $friendIds,
            'current_user_id' => $currentUser ? $currentUser->getId() : null,
            'user_scores' => $userScores,
        ]);
    }

    /**
     * Affiche les détails des scores dans la console pour le debug
     */
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