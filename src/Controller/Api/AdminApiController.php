<?php

namespace App\Controller\Api;

use App\Entity\Conversation;
use App\Entity\DataAccessRequest;
use App\Entity\Comment;
use App\Entity\Message;
use App\Entity\Notification;
use App\Entity\Post;
use App\Entity\Report;
use App\Entity\Article;
use App\Entity\Resource;
use App\Entity\Tag;
use App\Entity\User;
use App\Entity\UserQuestions;
use App\Repository\ArticleRepository;
use App\Repository\CommentRepository;
use App\Repository\DataAccessRequestRepository;
use App\Repository\MessageRepository;
use App\Repository\PostRepository;
use App\Repository\ReportRepository;
use App\Repository\ResourceRepository;
use App\Repository\TagRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Repository\UserRepository;
use App\Service\EmailService;

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

    /**
     * List reports for moderation queue
     */
    #[Route('/reports', name: 'api_admin_reports_list', methods: ['GET'])]
    public function getReports(
        Request $request,
        ReportRepository $reportRepository,
        PostRepository $postRepository,
        CommentRepository $commentRepository,
        UserRepository $userRepository,
        MessageRepository $messageRepository,
        ArticleRepository $articleRepository,
        ResourceRepository $resourceRepository
    ): JsonResponse {
        /** @var User|null $user */
        $user = $this->getUser();
        if (!$user || $user->getUserType() !== 1) {
            return $this->json(['error' => 'Accès refusé'], Response::HTTP_FORBIDDEN);
        }

        $status = trim((string) $request->query->get('status', ''));
        $criteria = [];
        if ($status !== '') {
            $criteria['status'] = $status;
        }

        $reports = $reportRepository->findBy($criteria, ['createdAt' => 'DESC']);

        $data = array_map(function (Report $report) use ($postRepository, $commentRepository, $userRepository, $messageRepository, $articleRepository, $resourceRepository) {
            return [
                'id' => $report->getId(),
                'status' => $report->getStatus(),
                'reason' => $report->getReason(),
                'details' => $report->getDetails(),
                'targetType' => $report->getTargetType(),
                'targetId' => $report->getTargetId(),
                'createdAt' => $report->getCreatedAt()?->format('c'),
                'reporter' => [
                    'id' => $report->getReporter()?->getId(),
                    'username' => $report->getReporter()?->getUsername(),
                ],
                'reviewedBy' => $report->getReviewedBy() ? [
                    'id' => $report->getReviewedBy()->getId(),
                    'username' => $report->getReviewedBy()->getUsername(),
                ] : null,
                'reviewedAt' => $report->getReviewedAt()?->format('c'),
                'adminNote' => $report->getAdminNote(),
                'targetSummary' => $this->getTargetSummary(
                    $report,
                    $postRepository,
                    $commentRepository,
                    $userRepository,
                    $messageRepository,
                    $articleRepository,
                    $resourceRepository
                ),
            ];
        }, $reports);

        return $this->json(['reports' => $data]);
    }

    /**
     * List posts flagged as sensitive content
     */
    #[Route('/sensitive-posts', name: 'api_admin_sensitive_posts_list', methods: ['GET'])]
    public function getSensitivePosts(PostRepository $postRepository): JsonResponse
    {
        /** @var User|null $user */
        $user = $this->getUser();
        if (!$user || $user->getUserType() !== 1) {
            return $this->json(['error' => 'Accès refusé'], Response::HTTP_FORBIDDEN);
        }

        $posts = $postRepository->findBy(['hasSensitiveContent' => true], ['creationDate' => 'DESC']);

        $data = array_map(function (Post $post) {
            $forum = $post->getForum();
            $author = $post->getUser();

            return [
                'id' => $post->getId(),
                'postId' => $post->getId(),
                'name' => $post->getName(),
                'description' => $post->getDescription(),
                'createdAt' => $post->getCreationDate()?->format('c'),
                'forumCategory' => $forum?->getTitle(),
                'forumSpecial' => $forum?->getSpecial(),
                'author' => $author?->getUsername(),
                'hasSensitiveContent' => $post->hasSensitiveContent(),
                'sensitiveContentWarnings' => $post->getSensitiveContentWarnings() ?? [],
                'targetSummary' => [
                    'exists' => true,
                    'postId' => $post->getId(),
                    'forumCategory' => $forum?->getTitle(),
                    'forumSpecial' => $forum?->getSpecial(),
                    'label' => $post->getName(),
                    'author' => $author?->getUsername(),
                ],
            ];
        }, $posts);

        return $this->json(['posts' => $data]);
    }

    /**
     * List RGPD data-access requests.
     */
    #[Route('/data-access-requests', name: 'api_admin_data_access_requests_list', methods: ['GET'])]
    public function getDataAccessRequests(
        Request $request,
        DataAccessRequestRepository $dataAccessRequestRepository
    ): JsonResponse {
        /** @var User|null $user */
        $user = $this->getUser();
        if (!$user || $user->getUserType() !== 1) {
            return $this->json(['error' => 'Accès refusé'], Response::HTTP_FORBIDDEN);
        }

        $status = trim((string) $request->query->get('status', ''));
        $criteria = [];
        if ($status !== '') {
            $criteria['status'] = $status;
        }

        $requests = $dataAccessRequestRepository->findBy($criteria, ['createdAt' => 'DESC']);

        $data = array_map(static function (DataAccessRequest $dataAccessRequest): array {
            return [
                'id' => $dataAccessRequest->getId(),
                'status' => $dataAccessRequest->getStatus(),
                'createdAt' => $dataAccessRequest->getCreatedAt()?->format('c'),
                'processedAt' => $dataAccessRequest->getProcessedAt()?->format('c'),
                'adminNote' => $dataAccessRequest->getAdminNote(),
                'requester' => [
                    'id' => $dataAccessRequest->getRequester()?->getId(),
                    'username' => $dataAccessRequest->getRequester()?->getUsername(),
                    'email' => $dataAccessRequest->getRequester()?->getEmail(),
                    'firstName' => $dataAccessRequest->getRequester()?->getFirstName(),
                    'lastName' => $dataAccessRequest->getRequester()?->getLastName(),
                ],
                'processedBy' => $dataAccessRequest->getProcessedBy() ? [
                    'id' => $dataAccessRequest->getProcessedBy()?->getId(),
                    'username' => $dataAccessRequest->getProcessedBy()?->getUsername(),
                ] : null,
            ];
        }, $requests);

        return $this->json(['requests' => $data]);
    }

    /**
     * Process RGPD data-access request.
     */
    #[Route('/data-access-requests/{id}', name: 'api_admin_data_access_requests_process', methods: ['PATCH'])]
    public function processDataAccessRequest(
        int $id,
        Request $request,
        DataAccessRequestRepository $dataAccessRequestRepository,
        EntityManagerInterface $entityManager,
        EmailService $emailService
    ): JsonResponse {
        /** @var User|null $user */
        $user = $this->getUser();
        if (!$user || $user->getUserType() !== 1) {
            return $this->json(['error' => 'Accès refusé'], Response::HTTP_FORBIDDEN);
        }

        $dataAccessRequest = $dataAccessRequestRepository->find($id);
        if (!$dataAccessRequest instanceof DataAccessRequest) {
            return $this->json(['error' => 'Demande non trouvée'], Response::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true);
        $status = trim((string) ($data['status'] ?? ''));
        $adminNote = trim((string) ($data['adminNote'] ?? ''));
        $previousStatus = $dataAccessRequest->getStatus();

        if (!in_array($status, [DataAccessRequest::STATUS_PROCESSED, DataAccessRequest::STATUS_REJECTED], true)) {
            return $this->json(['error' => 'Statut invalide'], Response::HTTP_BAD_REQUEST);
        }

        $dataAccessRequest->setStatus($status);
        $dataAccessRequest->setProcessedBy($user);
        $dataAccessRequest->setProcessedAt(new \DateTimeImmutable());
        $dataAccessRequest->setAdminNote($adminNote !== '' ? $adminNote : null);

        $requester = $dataAccessRequest->getRequester();
        if ($requester instanceof User && $previousStatus !== $status) {
            $this->createDataAccessRequestStatusNotification(
                $entityManager,
                $requester,
                $user,
                $dataAccessRequest,
                $status
            );
        }

        $entityManager->flush();

        // Send email with exported data if request was approved
        if ($status === DataAccessRequest::STATUS_PROCESSED) {
            try {
                $requester = $dataAccessRequest->getRequester();
                if ($requester instanceof User) {
                    $exportData = $this->buildUserDataExport($requester, $entityManager);
                    $emailService->sendDataExportEmail($requester, $exportData);
                }
            } catch (\Exception $e) {
                // Log error but don't fail the request
                error_log('Error sending RGPD data export email: ' . $e->getMessage());
            }
        }

        return $this->json([
            'message' => 'Demande mise à jour',
            'request' => [
                'id' => $dataAccessRequest->getId(),
                'status' => $dataAccessRequest->getStatus(),
                'processedAt' => $dataAccessRequest->getProcessedAt()?->format('c'),
            ],
        ]);
    }

    /**
     * Export user data for a data-access request.
     */
    #[Route('/data-access-requests/{id}/data', name: 'api_admin_data_access_requests_data', methods: ['GET'])]
    public function getDataAccessRequestData(
        int $id,
        DataAccessRequestRepository $dataAccessRequestRepository,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        /** @var User|null $user */
        $user = $this->getUser();
        if (!$user || $user->getUserType() !== 1) {
            return $this->json(['error' => 'Accès refusé'], Response::HTTP_FORBIDDEN);
        }

        $dataAccessRequest = $dataAccessRequestRepository->find($id);
        if (!$dataAccessRequest instanceof DataAccessRequest) {
            return $this->json(['error' => 'Demande non trouvée'], Response::HTTP_NOT_FOUND);
        }

        $requester = $dataAccessRequest->getRequester();
        if (!$requester instanceof User) {
            return $this->json(['error' => 'Utilisateur lié introuvable'], Response::HTTP_NOT_FOUND);
        }

        return $this->json([
            'request' => [
                'id' => $dataAccessRequest->getId(),
                'status' => $dataAccessRequest->getStatus(),
                'createdAt' => $dataAccessRequest->getCreatedAt()?->format('c'),
                'processedAt' => $dataAccessRequest->getProcessedAt()?->format('c'),
                'adminNote' => $dataAccessRequest->getAdminNote(),
            ],
            'data' => $this->buildUserDataExport($requester, $entityManager),
        ]);
    }

    /**
     * Process a report (reviewed/rejected)
     */
    #[Route('/reports/{id}', name: 'api_admin_reports_process', methods: ['PATCH'])]
    public function processReport(
        int $id,
        Request $request,
        ReportRepository $reportRepository,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        /** @var User|null $user */
        $user = $this->getUser();
        if (!$user || $user->getUserType() !== 1) {
            return $this->json(['error' => 'Accès refusé'], Response::HTTP_FORBIDDEN);
        }

        $report = $reportRepository->find($id);
        if (!$report) {
            return $this->json(['error' => 'Signalement non trouvé'], Response::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true);
        $status = trim((string) ($data['status'] ?? ''));
        $adminNote = trim((string) ($data['adminNote'] ?? ''));

        if (!in_array($status, [Report::STATUS_REVIEWED, Report::STATUS_REJECTED], true)) {
            return $this->json(['error' => 'Statut invalide'], Response::HTTP_BAD_REQUEST);
        }

        $report->setStatus($status);
        $report->setReviewedBy($user);
        $report->setReviewedAt(new \DateTimeImmutable());
        $report->setAdminNote($adminNote !== '' ? $adminNote : null);

        $entityManager->flush();

        return $this->json([
            'message' => 'Signalement mis à jour',
            'report' => [
                'id' => $report->getId(),
                'status' => $report->getStatus(),
                'reviewedAt' => $report->getReviewedAt()?->format('c'),
            ],
        ]);
    }

    /**
     * Apply an automated moderation action from a report.
     */
    #[Route('/reports/{id}/auto-action', name: 'api_admin_reports_auto_action', methods: ['POST'])]
    public function autoActionFromReport(
        int $id,
        Request $request,
        ReportRepository $reportRepository,
        PostRepository $postRepository,
        CommentRepository $commentRepository,
        MessageRepository $messageRepository,
        UserRepository $userRepository,
        ArticleRepository $articleRepository,
        ResourceRepository $resourceRepository,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        /** @var User|null $user */
        $user = $this->getUser();
        if (!$user || $user->getUserType() !== 1) {
            return $this->json(['error' => 'Accès refusé'], Response::HTTP_FORBIDDEN);
        }

        $report = $reportRepository->find($id);
        if (!$report) {
            return $this->json(['error' => 'Signalement non trouvé'], Response::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true);
        $action = trim((string) ($data['action'] ?? ''));
        $adminNote = trim((string) ($data['adminNote'] ?? ''));

        if (!in_array($action, ['delete_target', 'ban_author'], true)) {
            return $this->json(['error' => 'Action invalide'], Response::HTTP_BAD_REQUEST);
        }

        $targetType = $report->getTargetType();
        $targetId = (int) $report->getTargetId();
        $resultMessage = '';
        $contentAuthor = null;
        $removedContentType = null;
        $cleanedPendingReportsCount = 0;

        if ($action === 'delete_target') {
            if ($targetType === Report::TARGET_POST) {
                $post = $postRepository->find($targetId);
                if (!$post instanceof Post) {
                    return $this->json(['error' => 'Post non trouvé'], Response::HTTP_NOT_FOUND);
                }
                $contentAuthor = $post->getUser();
                $removedContentType = 'publication';
                $this->deletePostAttachments($post);
                $this->deletePostDependencies($post, $entityManager);
                $entityManager->remove($post);
                $resultMessage = 'Post supprimé';
            } elseif ($targetType === Report::TARGET_COMMENT) {
                $comment = $commentRepository->find($targetId);
                if (!$comment instanceof Comment) {
                    return $this->json(['error' => 'Commentaire non trouvé'], Response::HTTP_NOT_FOUND);
                }
                $contentAuthor = $comment->getUser();
                $removedContentType = 'commentaire';
                $entityManager->remove($comment);
                $resultMessage = 'Commentaire supprimé';
            } elseif ($targetType === Report::TARGET_MESSAGE) {
                $message = $messageRepository->find($targetId);
                if (!$message instanceof Message) {
                    return $this->json(['error' => 'Message non trouvé'], Response::HTTP_NOT_FOUND);
                }
                $contentAuthor = $message->getSender();
                $removedContentType = 'message';
                $entityManager->remove($message);
                $resultMessage = 'Message supprimé';
            } elseif ($targetType === Report::TARGET_ARTICLE) {
                $article = $articleRepository->find($targetId);
                if (!$article instanceof Article) {
                    return $this->json(['error' => 'Article non trouvé'], Response::HTTP_NOT_FOUND);
                }
                $contentAuthor = $article->getUser();
                $removedContentType = 'article';
                $this->deleteArticleAttachments($article);
                $entityManager->remove($article);
                $resultMessage = 'Article supprimé';
            } elseif ($targetType === Report::TARGET_RESOURCE) {
                $resource = $resourceRepository->find($targetId);
                if (!$resource instanceof Resource) {
                    return $this->json(['error' => 'Ressource non trouvée'], Response::HTTP_NOT_FOUND);
                }
                $contentAuthor = $resource->getUser();
                $removedContentType = 'ressource';
                $entityManager->remove($resource);
                $resultMessage = 'Ressource supprimée';
            } else {
                return $this->json(['error' => 'Suppression auto disponible uniquement pour post/commentaire/message/article/ressource'], Response::HTTP_BAD_REQUEST);
            }

            if ($contentAuthor instanceof User && is_string($removedContentType)) {
                $this->createModerationWarningNotification(
                    $entityManager,
                    $contentAuthor,
                    $user,
                    $report,
                    $removedContentType
                );
            }

            // Keep the current report as reviewed trace, but purge other pending
            // reports targeting the same deleted content.
            $cleanedPendingReportsCount = $reportRepository->deletePendingByTargetExceptId(
                (string) $targetType,
                $targetId,
                (int) $report->getId()
            );
        }

        if ($action === 'ban_author') {
            $author = null;

            if ($targetType === Report::TARGET_POST) {
                $author = $postRepository->find($targetId)?->getUser();
            } elseif ($targetType === Report::TARGET_COMMENT) {
                $author = $commentRepository->find($targetId)?->getUser();
            } elseif ($targetType === Report::TARGET_MESSAGE) {
                $author = $messageRepository->find($targetId)?->getSender();
            } elseif ($targetType === Report::TARGET_PROFILE) {
                $author = $userRepository->find($targetId);
            } elseif ($targetType === Report::TARGET_ARTICLE) {
                $author = $articleRepository->find($targetId)?->getUser();
            } elseif ($targetType === Report::TARGET_RESOURCE) {
                $author = $resourceRepository->find($targetId)?->getUser();
            }

            if (!$author instanceof User) {
                return $this->json(['error' => 'Auteur non trouvé'], Response::HTTP_NOT_FOUND);
            }

            if ($author->getUserType() === 1) {
                return $this->json(['error' => 'Impossible de bannir un administrateur'], Response::HTTP_BAD_REQUEST);
            }

            if ($author->isBanned()) {
                return $this->json(['error' => 'Auteur déjà banni'], Response::HTTP_CONFLICT);
            }

            $author->setIsBanned(true);
            $resultMessage = 'Auteur banni';
        }

        $report->setStatus(Report::STATUS_REVIEWED);
        $report->setReviewedBy($user);
        $report->setReviewedAt(new \DateTimeImmutable());

        $autoNote = sprintf('Auto-action: %s (%s).', $action, $resultMessage);
        $finalNote = trim($autoNote . ' ' . $adminNote);
        $report->setAdminNote($finalNote !== '' ? $finalNote : null);

        $entityManager->flush();

        return $this->json([
            'message' => 'Action automatique appliquée',
            'result' => $resultMessage,
            'cleanedPendingReports' => $cleanedPendingReportsCount,
            'report' => [
                'id' => $report->getId(),
                'status' => $report->getStatus(),
                'reviewedAt' => $report->getReviewedAt()?->format('c'),
            ],
        ]);
    }

    private function createModerationWarningNotification(
        EntityManagerInterface $entityManager,
        User $recipient,
        User $admin,
        Report $report,
        string $contentType
    ): void {
        $reason = trim((string) $report->getReason());
        if ($reason === '') {
            $reason = 'non précisé';
        }

        $notification = new Notification();
        $notification->setType('moderation_warning');
        $notification->setSender($admin);
        $notification->setRecipient($recipient);
        $notification->setStatus('pending');
        $notification->setData([
            'message' => sprintf(
                'Votre %s a été supprimé suite à un signalement. Motif : %s.',
                $contentType,
                $reason
            ),
            'reportId' => $report->getId(),
            'targetType' => $report->getTargetType(),
            'targetId' => $report->getTargetId(),
            'reason' => $reason,
        ]);

        $entityManager->persist($notification);
    }

    private function createDataAccessRequestStatusNotification(
        EntityManagerInterface $entityManager,
        User $recipient,
        User $admin,
        DataAccessRequest $dataAccessRequest,
        string $status
    ): void {
        $message = $status === DataAccessRequest::STATUS_PROCESSED
            ? 'Votre demande d\'accès à vos données RGPD a été acceptée. Un email avec le fichier JSON vous a été envoyé.'
            : 'Votre demande d\'accès à vos données RGPD a été refusée. Consultez la note d\'administration pour plus de détails.';

        $notification = new Notification();
        $notification->setType('data_access_request_update');
        $notification->setSender($admin);
        $notification->setRecipient($recipient);
        $notification->setStatus('unread');
        $notification->setData([
            'message' => $message,
            'requestId' => $dataAccessRequest->getId(),
            'decision' => $status,
            'adminNote' => $dataAccessRequest->getAdminNote(),
        ]);

        $entityManager->persist($notification);
    }

    private function deletePostDependencies(Post $post, EntityManagerInterface $entityManager): void
    {
        foreach ($post->getReplies() as $reply) {
            if ($reply instanceof Post) {
                $this->deletePostAttachments($reply);
                $this->deletePostDependencies($reply, $entityManager);
                $entityManager->remove($reply);
            }
        }

        foreach ($post->getComments() as $comment) {
            if ($comment instanceof Comment) {
                $entityManager->remove($comment);
            }
        }
    }

    private function deletePostAttachments(Post $post): void
    {
        $publicDirectory = dirname(__DIR__, 3) . '/public';

        $imagePath = $post->getImagePath();
        if (is_string($imagePath) && $imagePath !== '') {
            $imageFile = sprintf('%s/post_images/%s', $publicDirectory, $imagePath);
            if (is_file($imageFile)) {
                @unlink($imageFile);
            }
        }

        $pdfPath = $post->getPdfPath();
        if (is_string($pdfPath) && $pdfPath !== '') {
            $pdfFile = sprintf('%s/post_pdfs/%s', $publicDirectory, $pdfPath);
            if (is_file($pdfFile)) {
                @unlink($pdfFile);
            }
        }
    }

    private function deleteArticleAttachments(Article $article): void
    {
        $publicDirectory = dirname(__DIR__, 3) . '/public';

        $imagePath = $article->getImagePath();
        if (is_string($imagePath) && $imagePath !== '') {
            $imageFile = sprintf('%s/article_images/%s', $publicDirectory, $imagePath);
            if (is_file($imageFile)) {
                @unlink($imageFile);
            }
        }

        $pdfPath = $article->getPdfPath();
        if (is_string($pdfPath) && $pdfPath !== '') {
            $pdfFile = sprintf('%s/article_pdfs/%s', $publicDirectory, $pdfPath);
            if (is_file($pdfFile)) {
                @unlink($pdfFile);
            }
        }
    }

    private function getTargetSummary(
        Report $report,
        PostRepository $postRepository,
        CommentRepository $commentRepository,
        UserRepository $userRepository,
        MessageRepository $messageRepository,
        ArticleRepository $articleRepository,
        ResourceRepository $resourceRepository
    ): array {
        $type = $report->getTargetType();
        $targetId = (int) $report->getTargetId();

        if ($type === Report::TARGET_POST) {
            $post = $postRepository->find($targetId);
            if (!$post instanceof Post) {
                return ['exists' => false, 'label' => 'Post supprimé'];
            }

            $forum = $post->getForum();

            return [
                'exists' => true,
                'label' => $post->getName(),
                'postId' => $post->getId(),
                'forumCategory' => $forum?->getTitle(),
                'forumSpecial' => $forum?->getSpecial(),
                'author' => $post->getUser()?->getUsername(),
            ];
        }

        if ($type === Report::TARGET_COMMENT) {
            $comment = $commentRepository->find($targetId);
            if (!$comment instanceof Comment) {
                return ['exists' => false, 'label' => 'Commentaire supprimé'];
            }

            return [
                'exists' => true,
                'label' => mb_substr((string) $comment->getBody(), 0, 120),
                'author' => $comment->getUser()?->getUsername(),
            ];
        }

        if ($type === Report::TARGET_PROFILE) {
            $targetUser = $userRepository->find($targetId);
            if (!$targetUser instanceof User) {
                return ['exists' => false, 'label' => 'Profil supprimé'];
            }

            return [
                'exists' => true,
                'label' => $targetUser->getUsername(),
                'author' => $targetUser->getUsername(),
            ];
        }

        if ($type === Report::TARGET_MESSAGE) {
            $message = $messageRepository->find($targetId);
            if (!$message instanceof Message) {
                return ['exists' => false, 'label' => 'Message supprimé'];
            }

            $messageContent = trim((string) $message->getContent());
            if ($messageContent === '') {
                $messageContent = '[message vide]';
            }

            return [
                'exists' => true,
                'label' => $messageContent,
                'author' => $message->getSender()?->getUsername(),
            ];
        }

        if ($type === Report::TARGET_ARTICLE) {
            $article = $articleRepository->find($targetId);
            if (!$article instanceof Article) {
                return ['exists' => false, 'label' => 'Article supprimé'];
            }

            return [
                'exists' => true,
                'label' => $article->getTitle(),
                'author' => $article->getUser()?->getUsername(),
            ];
        }

        if ($type === Report::TARGET_RESOURCE) {
            $resource = $resourceRepository->find($targetId);
            if (!$resource instanceof Resource) {
                return ['exists' => false, 'label' => 'Ressource supprimée'];
            }

            return [
                'exists' => true,
                'label' => $resource->getTitle(),
                'author' => $resource->getUser()?->getUsername(),
            ];
        }

        return ['exists' => false, 'label' => 'Cible inconnue'];
    }

    private function buildUserDataExport(User $user, EntityManagerInterface $entityManager): array
    {
        $posts = $entityManager->getRepository(Post::class)->findBy(['user' => $user], ['creationDate' => 'DESC']);
        $comments = $entityManager->getRepository(Comment::class)->findBy(['user' => $user], ['creationDate' => 'DESC']);
        $articles = $entityManager->getRepository(Article::class)->findBy(['user' => $user], ['id' => 'DESC']);
        $resources = $entityManager->getRepository(Resource::class)->findBy(['user' => $user], ['id' => 'DESC']);
        $messages = $entityManager->getRepository(Message::class)->findBy(['sender' => $user], ['sentAt' => 'DESC']);
        $notifications = $entityManager->getRepository(Notification::class)->findBy(['recipient' => $user], ['createdAt' => 'DESC']);
        $questions = $entityManager->getRepository(UserQuestions::class)->findBy(['user' => $user]);
        $reports = $entityManager->getRepository(Report::class)->findBy(['reporter' => $user], ['createdAt' => 'DESC']);

        $conversations = $entityManager->getRepository(Conversation::class)->createQueryBuilder('c')
            ->where('c.user1 = :user OR c.user2 = :user')
            ->setParameter('user', $user)
            ->orderBy('c.id', 'DESC')
            ->getQuery()
            ->getResult();

        return [
            'exportedAt' => (new \DateTimeImmutable())->format('c'),
            'user' => [
                'id' => $user->getId(),
                'username' => $user->getUsername(),
                'email' => $user->getEmail(),
                'firstName' => $user->getFirstName(),
                'lastName' => $user->getLastName(),
                'affiliationLocation' => $user->getAffiliationLocation(),
                'specialization' => $user->getSpecialization(),
                'researchTopic' => $user->getResearchTopic(),
                'researcherTitle' => $user->getResearcherTitle(),
                'genre' => $user->getGenre(),
                'roles' => $user->getRoles(),
                'network' => $user->getNetwork(),
                'blocked' => $user->getBlocked(),
                'blockedBy' => $user->getBlockedBy(),
                'isBanned' => $user->isBanned(),
            ],
            'posts' => array_map(static function (Post $post): array {
                return [
                    'id' => $post->getId(),
                    'name' => $post->getName(),
                    'description' => $post->getDescription(),
                    'creationDate' => $post->getCreationDate()?->format('c'),
                    'lastActivity' => $post->getLastActivity()?->format('c'),
                    'forumId' => $post->getForum()?->getId(),
                    'forumTitle' => $post->getForum()?->getTitle(),
                    'isReply' => $post->getIsReply(),
                ];
            }, $posts),
            'comments' => array_map(static function (Comment $comment): array {
                return [
                    'id' => $comment->getId(),
                    'body' => $comment->getBody(),
                    'creationDate' => $comment->getCreationDate()?->format('c'),
                    'postId' => $comment->getPost()?->getId(),
                ];
            }, $comments),
            'articles' => array_map(static function (Article $article): array {
                return [
                    'id' => $article->getId(),
                    'title' => $article->getTitle(),
                    'link' => $article->getLink(),
                    'content' => $article->getContent(),
                    'relatedBookId' => $article->getRelatedBook()?->getId(),
                    'relatedAuthorId' => $article->getRelatedAuthor()?->getId(),
                ];
            }, $articles),
            'resources' => array_map(static function (Resource $resource): array {
                return [
                    'id' => $resource->getId(),
                    'title' => $resource->getTitle(),
                    'description' => $resource->getDescription(),
                    'link' => $resource->getLink(),
                    'page' => $resource->getPage(),
                ];
            }, $resources),
            'messages' => array_map(static function (Message $message): array {
                return [
                    'id' => $message->getId(),
                    'content' => $message->getContent(),
                    'sentAt' => $message->getSentAt()?->format('c'),
                    'conversationId' => $message->getConversation()?->getId(),
                ];
            }, $messages),
            'conversations' => array_map(static function (Conversation $conversation) use ($user): array {
                $otherParticipant = $conversation->getUser1()?->getId() === $user->getId()
                    ? $conversation->getUser2()
                    : $conversation->getUser1();

                return [
                    'id' => $conversation->getId(),
                    'user1Id' => $conversation->getUser1()?->getId(),
                    'user2Id' => $conversation->getUser2()?->getId(),
                    'otherParticipant' => $otherParticipant ? [
                        'id' => $otherParticipant->getId(),
                        'username' => $otherParticipant->getUsername(),
                    ] : null,
                ];
            }, $conversations),
            'notifications' => array_map(static function (Notification $notification): array {
                return [
                    'id' => $notification->getId(),
                    'type' => $notification->getType(),
                    'status' => $notification->getStatus(),
                    'isRead' => $notification->isRead(),
                    'createdAt' => $notification->getCreatedAt()->format('c'),
                    'data' => $notification->getData(),
                ];
            }, $notifications),
            'userQuestions' => array_map(static function (UserQuestions $question): array {
                return [
                    'id' => $question->getId(),
                    'question' => $question->getQuestion(),
                    'answer' => $question->getAnswer(),
                ];
            }, $questions),
            'reportsCreated' => array_map(static function (Report $report): array {
                return [
                    'id' => $report->getId(),
                    'targetType' => $report->getTargetType(),
                    'targetId' => $report->getTargetId(),
                    'reason' => $report->getReason(),
                    'details' => $report->getDetails(),
                    'status' => $report->getStatus(),
                    'createdAt' => $report->getCreatedAt()?->format('c'),
                ];
            }, $reports),
        ];
    }
}
