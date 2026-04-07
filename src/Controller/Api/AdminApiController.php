<?php

namespace App\Controller\Api;

use App\Entity\Comment;
use App\Entity\Message;
use App\Entity\Post;
use App\Entity\Report;
use App\Entity\Tag;
use App\Entity\User;
use App\Repository\CommentRepository;
use App\Repository\MessageRepository;
use App\Repository\PostRepository;
use App\Repository\ReportRepository;
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
        MessageRepository $messageRepository
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

        $data = array_map(function (Report $report) use ($postRepository, $commentRepository, $userRepository, $messageRepository) {
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
                    $messageRepository
                ),
            ];
        }, $reports);

        return $this->json(['reports' => $data]);
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

        if ($action === 'delete_target') {
            if ($targetType === Report::TARGET_POST) {
                $post = $postRepository->find($targetId);
                if (!$post instanceof Post) {
                    return $this->json(['error' => 'Post non trouvé'], Response::HTTP_NOT_FOUND);
                }
                $entityManager->remove($post);
                $resultMessage = 'Post supprimé';
            } elseif ($targetType === Report::TARGET_COMMENT) {
                $comment = $commentRepository->find($targetId);
                if (!$comment instanceof Comment) {
                    return $this->json(['error' => 'Commentaire non trouvé'], Response::HTTP_NOT_FOUND);
                }
                $entityManager->remove($comment);
                $resultMessage = 'Commentaire supprimé';
            } else {
                return $this->json(['error' => 'Suppression auto disponible uniquement pour post/commentaire'], Response::HTTP_BAD_REQUEST);
            }
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
            'report' => [
                'id' => $report->getId(),
                'status' => $report->getStatus(),
                'reviewedAt' => $report->getReviewedAt()?->format('c'),
            ],
        ]);
    }

    private function getTargetSummary(
        Report $report,
        PostRepository $postRepository,
        CommentRepository $commentRepository,
        UserRepository $userRepository,
        MessageRepository $messageRepository
    ): array {
        $type = $report->getTargetType();
        $targetId = (int) $report->getTargetId();

        if ($type === Report::TARGET_POST) {
            $post = $postRepository->find($targetId);
            if (!$post instanceof Post) {
                return ['exists' => false, 'label' => 'Post supprimé'];
            }

            return [
                'exists' => true,
                'label' => $post->getName(),
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

            return [
                'exists' => true,
                'label' => mb_substr((string) $message->getContent(), 0, 120),
                'author' => $message->getSender()?->getUsername(),
            ];
        }

        return ['exists' => false, 'label' => 'Cible inconnue'];
    }
}
