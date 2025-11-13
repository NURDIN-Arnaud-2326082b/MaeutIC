<?php

namespace App\Service;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Bundle\SecurityBundle\Security;

class OptimizedRecommendationService
{
    private const CACHE_TTL = 3600; // 1 heure
    private const MAX_RECOMMENDATIONS = 40;
    private const SCORE_WEIGHTS = [
        'specialization' => 0.4,
        'research_topic' => 0.3,
        'affiliation_location' => 0.2,
        'taggable_questions' => 0.1
    ];

    public function __construct(
        private UserRepository $userRepository,
        private EntityManagerInterface $entityManager,
        private CacheInterface $cache,
        private Security $security
    ) {}

    /**
     * Calcule les scores de recommandation pour tous les utilisateurs
     */
    public function calculateRecommendationScores(?User $currentUser = null, int $limit = self::MAX_RECOMMENDATIONS): array
    {
        if (!$currentUser) {
            $currentUser = $this->security->getUser();
        }
        
        if (!$currentUser) {
            return [];
        }

        $cacheKey = "recommendation_scores_{$currentUser->getId()}_{$limit}";
        
        return $this->cache->get($cacheKey, function() use ($currentUser, $limit) {
            $allUsers = $this->userRepository->findAll();
            $friendIds = $currentUser->getNetwork();
            $currentUserData = $this->extractUserData($currentUser);
            
            $scores = [];
            
            foreach ($allUsers as $user) {
                // Exclure l'utilisateur courant et ses amis
                if ($user->getId() === $currentUser->getId() || in_array($user->getId(), $friendIds)) {
                    continue;
                }
                
                $score = $this->calculateUserScore($currentUserData, $user);
                $scores[$user->getId()] = [
                    'user' => $user,
                    'score' => $score,
                    'details' => $this->getScoreDetails($currentUserData, $user)
                ];
            }
            
            // Trier par score décroissant (même les scores à 0 sont inclus)
            uasort($scores, function($a, $b) {
                return $b['score'] <=> $a['score'];
            });
            
            // DEBUG: Compter les scores non nuls
            $nonZeroScores = array_filter($scores, fn($item) => $item['score'] > 0);
            echo "<script>console.log('DEBUG SERVICE: Scores calculés: " . count($scores) . ", Scores non nuls: " . count($nonZeroScores) . "');</script>";
            
            // Limiter aux N premiers si demandé
            if ($limit < count($scores)) {
                return array_slice($scores, 0, $limit, true);
            }
            
            return $scores;
        });
    }

    /**
     * Extrait les données pertinentes pour la comparaison
     */
    private function extractUserData(User $user): array
    {
        $data = [
            'specialization' => $user->getSpecialization() ?? '',
            'research_topic' => $user->getResearchTopic() ?? '',
            'affiliation_location' => $user->getAffiliationLocation() ?? '',
            'taggable_questions' => []
        ];

        // Récupérer les réponses aux questions taggables
        foreach ($user->getUserQuestions() as $userQuestion) {
            $question = $userQuestion->getQuestion();
            if (in_array($question, ['Taggable Question 0', 'Taggable Question 1'])) {
                $answer = $userQuestion->getAnswer();
                if (!empty($answer)) {
                    $data['taggable_questions'][] = strtolower(trim($answer));
                }
            }
        }

        return $data;
    }

    /**
     * Calcule le score de similarité entre deux utilisateurs
     */
    private function calculateUserScore(array $currentUserData, User $otherUser): float
    {
        $otherUserData = $this->extractUserData($otherUser);
        $totalScore = 0.0;

        // Similarité de spécialisation
        if (!empty($currentUserData['specialization']) && !empty($otherUserData['specialization'])) {
            $specializationScore = $this->calculateStringSimilarity(
                $currentUserData['specialization'],
                $otherUserData['specialization']
            );
            $totalScore += $specializationScore * self::SCORE_WEIGHTS['specialization'];
        }

        // Similarité de sujet de recherche
        if (!empty($currentUserData['research_topic']) && !empty($otherUserData['research_topic'])) {
            $researchScore = $this->calculateStringSimilarity(
                $currentUserData['research_topic'],
                $otherUserData['research_topic']
            );
            $totalScore += $researchScore * self::SCORE_WEIGHTS['research_topic'];
        }

        // Similarité de localisation
        if (!empty($currentUserData['affiliation_location']) && !empty($otherUserData['affiliation_location'])) {
            $locationScore = $this->calculateLocationSimilarity(
                $currentUserData['affiliation_location'],
                $otherUserData['affiliation_location']
            );
            $totalScore += $locationScore * self::SCORE_WEIGHTS['affiliation_location'];
        }

        // Similarité des questions taggables
        if (!empty($currentUserData['taggable_questions']) && !empty($otherUserData['taggable_questions'])) {
            $questionScore = $this->calculateQuestionSimilarity(
                $currentUserData['taggable_questions'],
                $otherUserData['taggable_questions']
            );
            $totalScore += $questionScore * self::SCORE_WEIGHTS['taggable_questions'];
        }

        return round($totalScore, 4);
    }

    /**
     * Calcule la similarité entre deux chaînes de caractères
     */
    private function calculateStringSimilarity(string $str1, string $str2): float
    {
        $str1 = strtolower(trim($str1));
        $str2 = strtolower(trim($str2));
        
        if (empty($str1) || empty($str2)) {
            return 0.0;
        }
        
        if ($str1 === $str2) {
            return 1.0;
        }
        
        // Utilise similar_text pour une similarité basique
        similar_text($str1, $str2, $percent);
        return $percent / 100;
    }

    /**
     * Calcule la similarité de localisation
     */
    private function calculateLocationSimilarity(string $location1, string $location2): float
    {
        $location1 = strtolower(trim($location1));
        $location2 = strtolower(trim($location2));
        
        if (empty($location1) || empty($location2)) {
            return 0.0;
        }
        
        if ($location1 === $location2) {
            return 1.0;
        }
        
        // Vérifie si une localisation est contenue dans l'autre
        if (str_contains($location1, $location2) || str_contains($location2, $location1)) {
            return 0.7;
        }
        
        // Vérifie les mots communs
        $words1 = array_filter(explode(' ', $location1));
        $words2 = array_filter(explode(' ', $location2));
        $commonWords = array_intersect($words1, $words2);
        
        if (!empty($commonWords)) {
            return min(0.5, count($commonWords) / max(count($words1), count($words2)));
        }
        
        return 0.0;
    }

    /**
     * Calcule la similarité entre les réponses aux questions taggables
     */
    private function calculateQuestionSimilarity(array $questions1, array $questions2): float
    {
        if (empty($questions1) || empty($questions2)) {
            return 0.0;
        }
        
        $commonAnswers = array_intersect($questions1, $questions2);
        $totalAnswers = array_unique(array_merge($questions1, $questions2));
        
        if (empty($totalAnswers)) {
            return 0.0;
        }
        
        return count($commonAnswers) / count($totalAnswers);
    }

    /**
     * Retourne les détails du calcul de score
     */
    private function getScoreDetails(array $currentUserData, User $otherUser): array
    {
        $otherUserData = $this->extractUserData($otherUser);
        
        return [
            'specialization' => [
                'current' => $currentUserData['specialization'],
                'other' => $otherUserData['specialization'],
                'score' => $this->calculateStringSimilarity(
                    $currentUserData['specialization'],
                    $otherUserData['specialization']
                ) * self::SCORE_WEIGHTS['specialization']
            ],
            'research_topic' => [
                'current' => $currentUserData['research_topic'],
                'other' => $otherUserData['research_topic'],
                'score' => $this->calculateStringSimilarity(
                    $currentUserData['research_topic'],
                    $otherUserData['research_topic']
                ) * self::SCORE_WEIGHTS['research_topic']
            ],
            'affiliation_location' => [
                'current' => $currentUserData['affiliation_location'],
                'other' => $otherUserData['affiliation_location'],
                'score' => $this->calculateLocationSimilarity(
                    $currentUserData['affiliation_location'],
                    $otherUserData['affiliation_location']
                ) * self::SCORE_WEIGHTS['affiliation_location']
            ],
            'taggable_questions' => [
                'current' => $currentUserData['taggable_questions'],
                'other' => $otherUserData['taggable_questions'],
                'score' => $this->calculateQuestionSimilarity(
                    $currentUserData['taggable_questions'],
                    $otherUserData['taggable_questions']
                ) * self::SCORE_WEIGHTS['taggable_questions']
            ]
        ];
    }

    /**
     * Vide le cache des recommandations pour un utilisateur
     */
    public function clearUserCache(User $user): void
    {
        $cacheKey = "recommendation_scores_{$user->getId()}_" . self::MAX_RECOMMENDATIONS;
        $this->cache->delete($cacheKey);
    }
}