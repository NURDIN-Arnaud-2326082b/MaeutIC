<?php

namespace App\Controller\Api;

use App\Entity\Comment;
use App\Entity\Message;
use App\Entity\Post;
use App\Entity\Report;
use App\Repository\ArticleRepository;
use App\Entity\User;
use App\Repository\CommentRepository;
use App\Repository\MessageRepository;
use App\Repository\PostRepository;
use App\Repository\ReportRepository;
use App\Repository\ResourceRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/reports')]
class ReportApiController extends AbstractController
{
    #[Route('', name: 'api_reports_create', methods: ['POST'])]
    public function create(
        Request $request,
        ReportRepository $reportRepository,
        PostRepository $postRepository,
        CommentRepository $commentRepository,
        UserRepository $userRepository,
        MessageRepository $messageRepository,
        ArticleRepository $articleRepository,
        ResourceRepository $resourceRepository,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        /** @var User|null $currentUser */
        $currentUser = $this->getUser();
        if (!$currentUser) {
            return $this->json(['error' => 'Non authentifie'], Response::HTTP_UNAUTHORIZED);
        }

        $data = json_decode($request->getContent(), true);
        $targetType = strtolower(trim((string) ($data['targetType'] ?? '')));
        $targetId = (int) ($data['targetId'] ?? 0);
        $reason = trim((string) ($data['reason'] ?? ''));
        $details = trim((string) ($data['details'] ?? ''));

        $allowedTypes = [
            Report::TARGET_POST,
            Report::TARGET_COMMENT,
            Report::TARGET_PROFILE,
            Report::TARGET_MESSAGE,
            Report::TARGET_ARTICLE,
            Report::TARGET_RESOURCE,
        ];

        if (!in_array($targetType, $allowedTypes, true) || $targetId <= 0 || $reason === '') {
            return $this->json(['error' => 'Payload de signalement invalide'], Response::HTTP_BAD_REQUEST);
        }

        if (!$this->targetExists($targetType, $targetId, $postRepository, $commentRepository, $userRepository, $messageRepository, $articleRepository, $resourceRepository)) {
            return $this->json(['error' => 'Contenu non trouve'], Response::HTTP_NOT_FOUND);
        }

        if ($this->isOwnTarget($currentUser, $targetType, $targetId, $postRepository, $commentRepository, $messageRepository, $articleRepository, $resourceRepository)) {
            return $this->json(['error' => 'Vous ne pouvez pas signaler votre propre contenu'], Response::HTTP_BAD_REQUEST);
        }

        $existingPending = $reportRepository->findOneBy([
            'reporter' => $currentUser,
            'targetType' => $targetType,
            'targetId' => $targetId,
            'status' => Report::STATUS_PENDING,
        ]);
        if ($existingPending) {
            return $this->json(['error' => 'Un signalement est deja en cours pour ce contenu'], Response::HTTP_CONFLICT);
        }

        $report = new Report();
        $report->setReporter($currentUser);
        $report->setTargetType($targetType);
        $report->setTargetId($targetId);
        $report->setReason($reason);
        $report->setDetails($details !== '' ? $details : null);
        $report->setStatus(Report::STATUS_PENDING);
        $report->setCreatedAt(new \DateTimeImmutable());

        $entityManager->persist($report);
        $entityManager->flush();

        return $this->json([
            'message' => 'Signalement envoye',
            'report' => [
                'id' => $report->getId(),
                'status' => $report->getStatus(),
            ],
        ], Response::HTTP_CREATED);
    }

    private function targetExists(
        string $targetType,
        int $targetId,
        PostRepository $postRepository,
        CommentRepository $commentRepository,
        UserRepository $userRepository,
        MessageRepository $messageRepository,
        ArticleRepository $articleRepository,
        ResourceRepository $resourceRepository
    ): bool {
        return match ($targetType) {
            Report::TARGET_POST => $postRepository->find($targetId) !== null,
            Report::TARGET_COMMENT => $commentRepository->find($targetId) !== null,
            Report::TARGET_PROFILE => $userRepository->find($targetId) !== null,
            Report::TARGET_MESSAGE => $messageRepository->find($targetId) !== null,
            Report::TARGET_ARTICLE => $articleRepository->find($targetId) !== null,
            Report::TARGET_RESOURCE => $resourceRepository->find($targetId) !== null,
            default => false,
        };
    }

    private function isOwnTarget(
        User $currentUser,
        string $targetType,
        int $targetId,
        PostRepository $postRepository,
        CommentRepository $commentRepository,
        MessageRepository $messageRepository,
        ArticleRepository $articleRepository,
        ResourceRepository $resourceRepository
    ): bool {
        return match ($targetType) {
            Report::TARGET_POST => (($postRepository->find($targetId)?->getUser()?->getId()) === $currentUser->getId()),
            Report::TARGET_COMMENT => (($commentRepository->find($targetId)?->getUser()?->getId()) === $currentUser->getId()),
            Report::TARGET_PROFILE => ($targetId === $currentUser->getId()),
            Report::TARGET_MESSAGE => (($messageRepository->find($targetId)?->getSender()?->getId()) === $currentUser->getId()),
            Report::TARGET_ARTICLE => (($articleRepository->find($targetId)?->getUser()?->getId()) === $currentUser->getId()),
            Report::TARGET_RESOURCE => (($resourceRepository->find($targetId)?->getUser()?->getId()) === $currentUser->getId()),
            default => false,
        };
    }
}
