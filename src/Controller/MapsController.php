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
        $recommendationScores = [];

        if ($currentUser) {
            // Récupérer les amis (toujours affichés)
            $friends = $networkService->getUserNetwork($currentUser);
            $friendIds = array_map(fn($friend) => $friend->getId(), $friends);
            
            // Récupérer TOUS les utilisateurs sauf l'utilisateur courant et ses amis
            $allUsers = $userRepository->findAll();
            $nonFriendUsers = array_filter($allUsers, function($user) use ($currentUser, $friendIds) {
                return $user->getId() !== $currentUser->getId() && !in_array($user->getId(), $friendIds);
            });
            
            // Calculer les scores pour tous les non-amis
            $recommendationScores = $recommendationService->calculateRecommendationScores($currentUser, 1000); // Grand nombre pour avoir tous les scores
            
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
            }
            
            // Afficher les détails des scores dans la console (pour le debug)
            $this->displayScoreDetails($recommendationScores);
            
            // DEBUG
            echo "<script>console.log('DEBUG: Amis: " . count($friends) . ", Recommandés: " . count($recommendedUsers) . "');</script>";
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
            'recommendation_scores' => $recommendationScores,
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

    /**
     * Affiche les détails des scores dans la console pour le debug
     */
    private function displayScoreDetails(array $recommendationScores): void
    {
        echo "<script>";
        echo "console.log('=== DÉTAILS DES SCORES DE RECOMMANDATION ===');";
        
        $counter = 0;
        foreach ($recommendationScores as $userId => $scoreData) {
            $user = $scoreData['user'];
            $details = $scoreData['details'];
            
            echo "console.log('Utilisateur: {$user->getFirstName()} {$user->getLastName()} (ID: {$user->getId()})');";
            echo "console.log('Score total: {$scoreData['score']}');";
            echo "console.log('  - Spécialisation: " . ($details['specialization']['score'] ?? 0) . "');";
            echo "console.log('  - Sujet de recherche: " . ($details['research_topic']['score'] ?? 0) . "');";
            echo "console.log('  - Localisation: " . ($details['affiliation_location']['score'] ?? 0) . "');";
            echo "console.log('  - Questions taggables: " . ($details['taggable_questions']['score'] ?? 0) . "');";
            echo "console.log('---');";
            
            $counter++;
            if ($counter >= 10) { // Limiter à 10 pour ne pas surcharger la console
                break;
            }
        }
        
        echo "console.log('Total des recommandations calculées: " . count($recommendationScores) . "');";
        echo "console.log('================================');";
        echo "</script>";
    }
}