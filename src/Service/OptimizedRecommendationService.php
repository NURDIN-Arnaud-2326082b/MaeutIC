<?php

namespace App\Service;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Repository\PostRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Psr\Log\LoggerInterface;

class OptimizedRecommendationService
{
    private const CACHE_TTL = 3600; // 1 heure
    private const MAX_RECOMMENDATIONS = 40;
    
    // Nouveaux poids avec l'IA comportementale
    private const SCORE_WEIGHTS = [
        'behavioral' => 0.6,      // Nouveau : analyse des likes et popularité
        'specialization' => 0.16, // Réduit de 0.4 à 0.16
        'research_topic' => 0.12, // Réduit de 0.3 à 0.12
        'affiliation_location' => 0.08, // Réduit de 0.2 à 0.08
        'taggable_questions' => 0.04 // Réduit de 0.1 à 0.04
    ];

    public function __construct(
        private UserRepository $userRepository,
        private PostRepository $postRepository,
        private EntityManagerInterface $entityManager,
        private CacheInterface $cache,
        private Security $security,
        private LoggerInterface $logger
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

        // 🚨 CACHE COMPLÈTEMENT DÉSACTIVÉ POUR DEBUG
        // $cacheKey = "recommendation_scores_{$currentUser->getId()}_{$limit}";
        // return $this->cache->get($cacheKey, function() use ($currentUser, $limit) {
        
        $this->logger->info('🚨🚨🚨 SERVICE calculateRecommendationScores APPELÉ - CACHE DÉSACTIVÉ 🚨🚨🚨');
        $this->logger->info('👤 Current User ID: {userId}', ['userId' => $currentUser->getId()]);
        
        $startTime = microtime(true);
        
        $allUsers = $this->userRepository->findAll();
        $friendIds = $currentUser->getNetwork();
        
        $this->logger->info('📊 Total users in DB: {totalUsers}, Friends: {friends}', [
            'totalUsers' => count($allUsers),
            'friends' => count($friendIds)
        ]);
        
        // Récupérer les données comportementales en une seule requête
        $behavioralData = $this->getBehavioralData($currentUser);
        
        $scores = [];
        $categoryScores = []; // Pour suivre la diversité
        
        foreach ($allUsers as $user) {
            // Exclure l'utilisateur courant et ses amis
            if ($user->getId() === $currentUser->getId() || in_array($user->getId(), $friendIds)) {
                continue;
            }
            
            $score = $this->calculateUserScore($currentUser, $user, $behavioralData, $categoryScores);
            $scores[$user->getId()] = [
                'user' => $user,
                'score' => $score,
                'details' => $this->getScoreDetails($currentUser, $user, $behavioralData)
            ];
        }
        
        // DEBUG: Afficher le nombre d'utilisateurs évalués
        $this->logger->info('✅ DEBUG SERVICE: Utilisateurs évalués: {count}', ['count' => count($scores)]);
        
        // CORRECTION: TOUJOURS appliquer un score minimum pour éviter les scores à 0
        $scores = $this->applyMinimumScores($scores, $currentUser);
        
        // Appliquer la diversité - booster les scores des catégories sous-représentées
        $scores = $this->applyDiversityBoost($scores, $categoryScores);
        
        // Trier par score décroissant
        uasort($scores, function($a, $b) {
            return $b['score'] <=> $a['score'];
        });
        
        $executionTime = round((microtime(true) - $startTime) * 1000, 2);
        
        // DEBUG: Afficher quelques scores pour vérifier
        $sampleScores = array_slice($scores, 0, 3);
        foreach ($sampleScores as $sample) {
            $this->logger->info('📈 DEBUG SAMPLE: Utilisateur {userId} - Score: {score}', [
                'userId' => $sample['user']->getId(),
                'score' => $sample['score']
            ]);
        }
        
        $this->logger->info('⏱️ DEBUG SERVICE: Calcul terminé en {time}ms - Scores: {count}', [
            'time' => $executionTime,
            'count' => count($scores)
        ]);
        
        return $scores;
        // }); // Fin du cache désactivé
    }

    /**
     * Applique un score minimum à TOUS les utilisateurs pour éviter les scores à 0
     */
    private function applyMinimumScores(array $scores, User $currentUser): array
    {
        $currentUserData = $this->extractUserData($currentUser);
        
        foreach ($scores as $userId => &$scoreData) {
            $user = $scoreData['user'];
            $otherUserData = $this->extractUserData($user);
            
            // Calculer un score de base basé sur les critères traditionnels
            $baseScore = 0.0;
            
            // Similarité de spécialisation
            if (!empty($currentUserData['specialization']) && !empty($otherUserData['specialization'])) {
                $specializationScore = $this->calculateStringSimilarity(
                    $currentUserData['specialization'],
                    $otherUserData['specialization']
                );
                $baseScore += $specializationScore * 0.3;
            }
            
            // Similarité de sujet de recherche
            if (!empty($currentUserData['research_topic']) && !empty($otherUserData['research_topic'])) {
                $researchScore = $this->calculateStringSimilarity(
                    $currentUserData['research_topic'],
                    $otherUserData['research_topic']
                );
                $baseScore += $researchScore * 0.2;
            }
            
            // Similarité de localisation
            if (!empty($currentUserData['affiliation_location']) && !empty($otherUserData['affiliation_location'])) {
                $locationScore = $this->calculateLocationSimilarity(
                    $currentUserData['affiliation_location'],
                    $otherUserData['affiliation_location']
                );
                $baseScore += $locationScore * 0.1;
            }
            
            // Score minimum garanti pour TOUS les utilisateurs
            $minScore = 0.05; // Score minimum absolu
            $guaranteedScore = max($minScore, $baseScore);
            
            // Si le score actuel est très bas, utiliser le score garanti
            if ($scoreData['score'] < 0.1) {
                $scoreData['score'] = $guaranteedScore;
                $scoreData['details']['guaranteed_score'] = $guaranteedScore;
                $scoreData['details']['original_score'] = $scoreData['score'];
            } else {
                // Sinon, ajouter le score de base au score existant
                $scoreData['score'] += $baseScore * 0.3; // Pondération réduite
                $scoreData['details']['base_score_added'] = $baseScore * 0.3;
            }
            
            // Assurer que le score ne dépasse pas 1.0
            $scoreData['score'] = min(1.0, $scoreData['score']);
        }
        
        $this->logger->info('🛡️ DEBUG SERVICE: Scores minimum appliqués pour {count} utilisateurs', ['count' => count($scores)]);
        
        return $scores;
    }

    /**
     * Récupère toutes les données comportementales en une seule requête optimisée
     */
    private function getBehavioralData(User $currentUser): array
    {
        // 🚨 CACHE COMPLÈTEMENT DÉSACTIVÉ POUR DEBUG
        // $cacheKey = "behavioral_data_{$currentUser->getId()}";
        // return $this->cache->get($cacheKey, function() use ($currentUser) {
        
        $this->logger->info('🔍 GET BEHAVIORAL DATA APPELÉ - CACHE DÉSACTIVÉ');
        $this->logger->info('👤 Current User ID: {userId}', ['userId' => $currentUser->getId()]);
        
        // Vérifier les likes directement en base de données
        $this->debugLikesInDatabase($currentUser);
        
        // 1. Récupérer les forums des posts likés par l'utilisateur
        $userLikesQuery = $this->entityManager->createQuery("
            SELECT f.id, f.title, COUNT(pl.id) as likeCount
            FROM App\Entity\PostLike pl
            JOIN pl.post p
            JOIN p.forum f
            WHERE pl.user = :user
            GROUP BY f.id, f.title
            ORDER BY likeCount DESC
        ")->setParameter('user', $currentUser);
        
        $userInterests = $userLikesQuery->getResult();
        
        // DEBUG CRITIQUE
        $this->logger->info('🎯 Forums likés trouvés par la requête: {count}', ['count' => count($userInterests)]);
        if (empty($userInterests)) {
            $this->logger->info('❌❌❌ AUCUN FORUM LIKÉ TROUVÉ - Vérifiez que l\'utilisateur a bien liké des posts');
        } else {
            foreach ($userInterests as $interest) {
                $this->logger->info('✅ Forum liké: {title} (ID: {id}), Likes: {likes}', [
                    'title' => $interest['title'],
                    'id' => $interest['id'],
                    'likes' => $interest['likeCount']
                ]);
            }
        }
        
        $forumActivity = [];
        
        // 2. Récupérer TOUS les utilisateurs qui postent dans ces forums (CRITIQUE)
        if (!empty($userInterests)) {
            $forumIds = array_column($userInterests, 'id');
            $this->logger->info('🔎 Recherche utilisateurs dans les forums: {forumIds}', ['forumIds' => implode(', ', $forumIds)]);
            
            $forumUsersQuery = $this->entityManager->createQuery("
                SELECT DISTINCT IDENTITY(p.user) as userId, f.id as forumId, f.title as forumTitle,
                       COUNT(p.id) as postCount,
                       COUNT(pl.id) as totalLikes,
                       COUNT(DISTINCT c.id) as totalComments,
                       (COUNT(pl.id) * 0.7 + COUNT(DISTINCT c.id) * 0.3) as popularityScore
                FROM App\Entity\Post p
                JOIN p.forum f
                LEFT JOIN App\Entity\PostLike pl WITH pl.post = p
                LEFT JOIN App\Entity\Comment c WITH c.post = p
                WHERE p.user IS NOT NULL 
                AND f.id IN (:forumIds)
                GROUP BY p.user, f.id, f.title
                HAVING COUNT(p.id) > 0
                ORDER BY f.id, popularityScore DESC
            ")->setParameter('forumIds', $forumIds);
            
            $forumUsers = $forumUsersQuery->getResult();
            
            $this->logger->info('👥 Utilisateurs trouvés dans ces forums: {count}', ['count' => count($forumUsers)]);
            
            if (empty($forumUsers)) {
                $this->logger->info('❌❌❌ AUCUN UTILISATEUR TROUVÉ dans les forums likés');
            } else {
                foreach ($forumUsers as $forumUser) {
                    $this->logger->info('✅ User {userId} dans forum {forumTitle} - Posts: {posts}, Popularité: {popularity}', [
                        'userId' => $forumUser['userId'],
                        'forumTitle' => $forumUser['forumTitle'],
                        'posts' => $forumUser['postCount'],
                        'popularity' => $forumUser['popularityScore']
                    ]);
                }
            }
            
            // 3. Structurer les données
            foreach ($forumUsers as $forumUser) {
                $forumId = $forumUser['forumId'];
                $userId = $forumUser['userId'];
                
                if (!isset($forumActivity[$forumId])) {
                    $forumActivity[$forumId] = [
                        'title' => $forumUser['forumTitle'],
                        'allUsers' => [],
                        'topUsers' => []
                    ];
                }
                
                // Ajouter TOUS les utilisateurs
                $forumActivity[$forumId]['allUsers'][$userId] = [
                    'postCount' => $forumUser['postCount'],
                    'popularityScore' => (float) $forumUser['popularityScore'],
                    'totalLikes' => $forumUser['totalLikes'],
                    'totalComments' => $forumUser['totalComments']
                ];
                
                // Garder aussi les top users
                if (count($forumActivity[$forumId]['topUsers']) < 10 || 
                    $forumUser['popularityScore'] > 3) {
                    $forumActivity[$forumId]['topUsers'][$userId] = (float) $forumUser['popularityScore'];
                }
            }
        }
        
        $this->logger->info('📁 Forum activity final: {count} forums avec activité', ['count' => count($forumActivity)]);
        $this->logger->info('=======================');
        
        return [
            'userInterests' => $userInterests,
            'forumActivity' => $forumActivity
        ];
        // }); // Fin du cache désactivé
    }

    /**
     * Calcule le score complet entre deux utilisateurs
     */
    private function calculateUserScore(User $currentUser, User $otherUser, array $behavioralData, array &$categoryScores): float
    {
        $totalScore = 0.0;
        
        // 1. Score comportemental (60%)
        $behavioralScore = $this->calculateBehavioralScore($currentUser, $otherUser, $behavioralData, $categoryScores);
        $totalScore += $behavioralScore * self::SCORE_WEIGHTS['behavioral'];
        
        // 2. Scores traditionnels (40%)
        $traditionalScore = $this->calculateTraditionalScore($currentUser, $otherUser);
        $totalScore += $traditionalScore;
        
        // DEBUG: Afficher le détail du calcul pour cet utilisateur
        if ($totalScore == 0) {
            $this->logger->info('🔍 DEBUG CALC: User {userId} - Behavioral: {behavioral}, Traditional: {traditional}', [
                'userId' => $otherUser->getId(),
                'behavioral' => $behavioralScore,
                'traditional' => $traditionalScore
            ]);
        }
        
        return round($totalScore, 4);
    }

    /**
     * Calcule le score basé sur les critères traditionnels
     */
    private function calculateTraditionalScore(User $currentUser, User $otherUser): float
    {
        $currentUserData = $this->extractUserData($currentUser);
        $otherUserData = $this->extractUserData($otherUser);
        $score = 0.0;
        
        // Similarité de spécialisation
        if (!empty($currentUserData['specialization']) && !empty($otherUserData['specialization'])) {
            $specializationScore = $this->calculateStringSimilarity(
                $currentUserData['specialization'],
                $otherUserData['specialization']
            );
            $score += $specializationScore * self::SCORE_WEIGHTS['specialization'];
        }
        
        // Similarité de sujet de recherche
        if (!empty($currentUserData['research_topic']) && !empty($otherUserData['research_topic'])) {
            $researchScore = $this->calculateStringSimilarity(
                $currentUserData['research_topic'],
                $otherUserData['research_topic']
            );
            $score += $researchScore * self::SCORE_WEIGHTS['research_topic'];
        }
        
        // Similarité de localisation
        if (!empty($currentUserData['affiliation_location']) && !empty($otherUserData['affiliation_location'])) {
            $locationScore = $this->calculateLocationSimilarity(
                $currentUserData['affiliation_location'],
                $otherUserData['affiliation_location']
            );
            $score += $locationScore * self::SCORE_WEIGHTS['affiliation_location'];
        }
        
        // Similarité des questions taggables
        if (!empty($currentUserData['taggable_questions']) && !empty($otherUserData['taggable_questions'])) {
            $questionScore = $this->calculateQuestionSimilarity(
                $currentUserData['taggable_questions'],
                $otherUserData['taggable_questions']
            );
            $score += $questionScore * self::SCORE_WEIGHTS['taggable_questions'];
        }
        
        return $score;
    }

    /**
     * Calcule le score basé sur le comportement (likes et activité)
     */
    private function calculateBehavioralScore(User $currentUser, User $otherUser, array $behavioralData, array &$categoryScores): float
    {
        $score = 0.0;
        $userInterests = $behavioralData['userInterests'];
        $forumActivity = $behavioralData['forumActivity'];
        
        // DEBUG: Vérifier les données d'entrée
        $this->logger->info('🎯 CALCUL BEHAVIORAL SCORE pour User {userId}', ['userId' => $otherUser->getId()]);
        $this->logger->info('   User interests count: {count}', ['count' => count($userInterests)]);
        $this->logger->info('   Forum activity count: {count}', ['count' => count($forumActivity)]);
        
        // Si l'utilisateur n'a pas de likes, on retourne un score neutre
        if (empty($userInterests)) {
            $this->logger->info('   ❌ AUCUN LIKE - Score neutre 0.3');
            return 0.3;
        }
        
        // Total des likes de l'utilisateur pour normalisation
        $totalLikes = array_sum(array_column($userInterests, 'likeCount'));
        if ($totalLikes === 0) {
            $this->logger->info('   ❌ TOTAL LIKES = 0 - Score neutre 0.3');
            return 0.3;
        }
        
        $this->logger->info('   Total likes: {totalLikes}', ['totalLikes' => $totalLikes]);
        
        foreach ($userInterests as $interest) {
            $forumId = $interest['id'];
            $forumTitle = $interest['title'];
            $likeCount = $interest['likeCount'];
            
            $this->logger->info('   🎯 Forum: {title} (ID: {id}), Likes: {likes}', [
                'title' => $forumTitle,
                'id' => $forumId,
                'likes' => $likeCount
            ]);
            
            // Poids basé sur l'intérêt de l'utilisateur pour ce forum
            $interestWeight = $likeCount / $totalLikes;
            $this->logger->info('     Interest weight: {weight}', ['weight' => $interestWeight]);
            
            // Vérifier si l'autre utilisateur est actif dans ce forum
            if (isset($forumActivity[$forumId])) {
                $allUsers = $forumActivity[$forumId]['allUsers'];
                $topUsers = $forumActivity[$forumId]['topUsers'];
                
                $this->logger->info('     Forum trouvé - All users: {all}, Top users: {top}', [
                    'all' => count($allUsers),
                    'top' => count($topUsers)
                ]);
                
                // L'utilisateur poste dans ce forum → SCORE DE BASE
                if (isset($allUsers[$otherUser->getId()])) {
                    $userData = $allUsers[$otherUser->getId()];
                    
                    $this->logger->info('     ✅ USER TROUVÉ dans ce forum!');
                    $this->logger->info('       User data: {data}', ['data' => json_encode($userData)]);
                    
                    // Score de base pour activité dans le forum
                    $baseScore = 0.4;
                    
                    // BONUS de popularité si c'est un top user
                    $popularityBonus = 0.0;
                    if (isset($topUsers[$otherUser->getId()])) {
                        $popularityScore = $topUsers[$otherUser->getId()];
                        $normalizedPopularity = min(0.4, $popularityScore / 10);
                        $popularityBonus = $normalizedPopularity;
                        $this->logger->info('       🏆 Popularity bonus: {bonus} (score: {score})', [
                            'bonus' => $popularityBonus,
                            'score' => $popularityScore
                        ]);
                    }
                    
                    // BONUS d'engagement (likes et commentaires)
                    $engagementBonus = 0.0;
                    if ($userData['totalLikes'] > 0 || $userData['totalComments'] > 0) {
                        $engagementScore = min(0.2, 
                            ($userData['totalLikes'] * 0.015) + 
                            ($userData['totalComments'] * 0.01)
                        );
                        $engagementBonus = $engagementScore;
                        $this->logger->info('       💬 Engagement bonus: {bonus} (likes: {likes}, comments: {comments})', [
                            'bonus' => $engagementBonus,
                            'likes' => $userData['totalLikes'],
                            'comments' => $userData['totalComments']
                        ]);
                    }
                    
                    $forumScore = $interestWeight * ($baseScore + $popularityBonus + $engagementBonus);
                    $score += $forumScore;
                    
                    $this->logger->info('       📊 Forum score: {forumScore} (base: {base} + pop: {pop} + eng: {eng}) * weight: {weight}', [
                        'forumScore' => $forumScore,
                        'base' => $baseScore,
                        'pop' => $popularityBonus,
                        'eng' => $engagementBonus,
                        'weight' => $interestWeight
                    ]);
                    $this->logger->info('       📈 Score total: {score}', ['score' => $score]);
                    
                    // Suivre les scores par catégorie pour la diversité
                    if (!isset($categoryScores[$forumId])) {
                        $categoryScores[$forumId] = [
                            'title' => $forumTitle,
                            'totalScore' => 0.0,
                            'userCount' => 0
                        ];
                    }
                    $categoryScores[$forumId]['totalScore'] += $forumScore;
                    $categoryScores[$forumId]['userCount']++;
                } else {
                    $this->logger->info('     ❌ User NOT FOUND in forum users list');
                    $this->logger->info('       Available users: {users}', ['users' => implode(', ', array_keys($allUsers))]);
                }
            } else {
                $this->logger->info('     ❌ Forum NOT FOUND in forum activity');
            }
        }
        
        // Si l'utilisateur est actif dans vos forums préférés, il a au moins un score décent
        $finalScore = $score > 0 ? min(1.0, $score) : 0.1;
        $this->logger->info('     🎯 FINAL BEHAVIORAL SCORE: {score}', ['score' => $finalScore]);
        $this->logger->info('     =======================');
        
        return $finalScore;
    }

    /**
     * Applique un boost de diversité aux scores
     */
    private function applyDiversityBoost(array $scores, array $categoryScores): array
    {
        if (empty($categoryScores)) return $scores;
        
        // Calculer la moyenne des scores par catégorie
        $categoryAverages = [];
        foreach ($categoryScores as $forumId => $data) {
            if ($data['userCount'] > 0) {
                $categoryAverages[$forumId] = $data['totalScore'] / $data['userCount'];
            }
        }
        
        if (empty($categoryAverages)) return $scores;
        
        $globalAverage = array_sum($categoryAverages) / count($categoryAverages);
        
        // Appliquer un boost aux catégories sous-représentées
        foreach ($scores as $userId => &$scoreData) {
            $user = $scoreData['user'];
            $userCategories = $this->getUserTopCategories($user, $categoryScores);
            
            $diversityBoost = 0.0;
            foreach ($userCategories as $forumId => $userScore) {
                $categoryAvg = $categoryAverages[$forumId] ?? 0;
                if ($categoryAvg < $globalAverage * 0.7) { // Catégorie sous-représentée
                    $boost = ($globalAverage - $categoryAvg) * 0.3; // Boost modéré
                    $diversityBoost += $boost;
                }
            }
            
            $scoreData['score'] = min(1.0, $scoreData['score'] + $diversityBoost);
            $scoreData['details']['diversity_boost'] = $diversityBoost;
        }
        
        return $scores;
    }

    /**
     * Récupère les catégories principales d'un utilisateur
     */
    private function getUserTopCategories(User $user, array $categoryScores): array
    {
        // Cette méthode pourrait être optimisée avec une requête directe
        // Pour l'instant, on retourne un tableau vide - à améliorer si nécessaire
        return [];
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
     * Retourne les détails du calcul de score
     */
    private function getScoreDetails(User $currentUser, User $otherUser, array $behavioralData): array
    {
        $tempCategoryScores = [];
        $behavioralScore = $this->calculateBehavioralScore($currentUser, $otherUser, $behavioralData, $tempCategoryScores);
        
        $traditionalScore = $this->calculateTraditionalScore($currentUser, $otherUser);
        
        return [
            'behavioral' => [
                'score' => $behavioralScore * self::SCORE_WEIGHTS['behavioral'],
                'description' => 'Analyse des likes et activité dans les forums'
            ],
            'traditional' => [
                'score' => $traditionalScore,
                'description' => 'Critères traditionnels (spécialisation, recherche, etc.)'
            ]
        ];
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
        
        if (str_contains($location1, $location2) || str_contains($location2, $location1)) {
            return 0.7;
        }
        
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
     * Vide le cache des recommandations pour un utilisateur
     */
    public function clearUserCache(User $user): void
    {
        $cacheKey = "recommendation_scores_{$user->getId()}_" . self::MAX_RECOMMENDATIONS;
        $behavioralCacheKey = "behavioral_data_{$user->getId()}";
        $this->cache->delete($cacheKey);
        $this->cache->delete($behavioralCacheKey);
        $this->logger->info('🗑️ Cache vidé pour user: {userId}', ['userId' => $user->getId()]);
    }

    /**
     * DEBUG: Vérifie les likes directement en base de données
     */
    private function debugLikesInDatabase(User $currentUser): void
    {
        try {
            $conn = $this->entityManager->getConnection();
            
            // Compter le nombre total de likes
            $sql = "SELECT COUNT(*) as like_count FROM post_like WHERE user_id = :userId";
            $stmt = $conn->prepare($sql);
            $stmt->bindValue('userId', $currentUser->getId());
            $result = $stmt->executeQuery();
            $likeCount = $result->fetchOne();
            
            $this->logger->info('📊 LIKES EN BASE DE DONNÉES: {likeCount} likes pour user {userId}', [
                'likeCount' => $likeCount,
                'userId' => $currentUser->getId()
            ]);
            
            // Détail des likes
            $sql = "SELECT p.id as post_id, f.id as forum_id, f.title as forum_title 
                    FROM post_like pl 
                    JOIN post p ON pl.post_id = p.id 
                    JOIN forum f ON p.forum_id = f.id 
                    WHERE pl.user_id = :userId";
            $stmt = $conn->prepare($sql);
            $stmt->bindValue('userId', $currentUser->getId());
            $result = $stmt->executeQuery();
            $likes = $result->fetchAllAssociative();
            
            $this->logger->info('📝 Détail des likes: {count} posts likés', ['count' => count($likes)]);
            foreach ($likes as $like) {
                $this->logger->info('   - Post {postId} dans forum: {forumTitle} (ID: {forumId})', [
                    'postId' => $like['post_id'],
                    'forumTitle' => $like['forum_title'],
                    'forumId' => $like['forum_id']
                ]);
            }
            
            if (count($likes) === 0) {
                $this->logger->info('❌❌❌ AUCUN LIKE EN BASE DE DONNÉES - Le problème est ici!');
            }
        } catch (\Exception $e) {
            $this->logger->error('❌ ERREUR SQL dans debugLikesInDatabase: {error}', ['error' => $e->getMessage()]);
        }
    }

    /**
     * DEBUG: Affiche les données comportementales détaillées
     */
    private function debugBehavioralData(User $currentUser, array $behavioralData): void
    {
        $userInterests = $behavioralData['userInterests'];
        $forumActivity = $behavioralData['forumActivity'];
        
        $this->logger->info('=== DEBUG DONNÉES COMPORTEMENTALES ===');
        
        // Centres d'intérêt de l'utilisateur
        $this->logger->info('🎯 CENTRES D\'INTÉRÊT DE L\'UTILISATEUR:');
        if (empty($userInterests)) {
            $this->logger->info('  ⚠️ AUCUN LIKE TROUVÉ');
        } else {
            $totalLikes = array_sum(array_column($userInterests, 'likeCount'));
            $this->logger->info('  Total likes: {totalLikes}', ['totalLikes' => $totalLikes]);
            foreach ($userInterests as $interest) {
                $percentage = round(($interest['likeCount'] / $totalLikes) * 100, 1);
                $this->logger->info('  - {title}: {likes} likes ({percentage}%)', [
                    'title' => $interest['title'],
                    'likes' => $interest['likeCount'],
                    'percentage' => $percentage
                ]);
            }
        }
        
        // Activité par forum
        $this->logger->info('🏆 ACTIVITÉ PAR FORUM:');
        if (empty($forumActivity)) {
            $this->logger->info('  ⚠️ AUCUNE ACTIVITÉ TROUVÉE');
        } else {
            foreach ($forumActivity as $forumId => $data) {
                $allUserCount = count($data['allUsers']);
                $topUserCount = count($data['topUsers']);
                $this->logger->info('  - {title}: {all} utilisateurs actifs, {top} top users', [
                    'title' => $data['title'],
                    'all' => $allUserCount,
                    'top' => $topUserCount
                ]);
                foreach ($data['topUsers'] as $userId => $popularityScore) {
                    $this->logger->info('    * User {userId}: score {score}', [
                        'userId' => $userId,
                        'score' => $popularityScore
                    ]);
                }
            }
        }
        
        $this->logger->info('================================');
    }
}