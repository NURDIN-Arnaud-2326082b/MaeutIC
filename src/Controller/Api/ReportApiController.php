<?php

namespace App\Controller\Api;

use App\Entity\Article;
use App\Entity\Comment;
use App\Entity\Message;
use App\Entity\Post;
use App\Entity\Report;
use App\Entity\Resource;
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
    private const REASON_LABELS = [
        'spam' => 'Spam',
        'harassment' => 'Harcèlement',
        'inappropriate_content' => 'Contenu inapproprié',
        'impersonation' => "Usurpation d'identité",
        'other' => 'Autre',
    ];

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
        if (!is_array($data)) {
            return $this->json(['error' => 'JSON invalide'], Response::HTTP_BAD_REQUEST);
        }
        $targetType = strtolower(trim((string) ($data['targetType'] ?? '')));
        $targetId = (int) ($data['targetId'] ?? 0);
        $reason = $this->resolveReason($data);
        $details = trim((string) ($data['details'] ?? ''));

        $allowedTypes = [
            Report::TARGET_POST,
            Report::TARGET_COMMENT,
            Report::TARGET_PROFILE,
            Report::TARGET_MESSAGE,
            Report::TARGET_ARTICLE,
            Report::TARGET_RESOURCE,
        ];

        if (!in_array($targetType, $allowedTypes, true) || $targetId <= 0 || !is_string($reason) || $reason === '') {
            return $this->json(['error' => 'Payload de signalement invalide'], Response::HTTP_BAD_REQUEST);
        }

        $target = $this->findTargetEntity(
            $targetType,
            $targetId,
            $postRepository,
            $commentRepository,
            $userRepository,
            $messageRepository,
            $articleRepository,
            $resourceRepository
        );

        if ($target === null) {
            return $this->json(['error' => 'Contenu non trouve'], Response::HTTP_NOT_FOUND);
        }

        if ($this->isOwnTarget($currentUser, $targetType, $targetId, $target)) {
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

    private function findTargetEntity(
        string $targetType,
        int $targetId,
        PostRepository $postRepository,
        CommentRepository $commentRepository,
        UserRepository $userRepository,
        MessageRepository $messageRepository,
        ArticleRepository $articleRepository,
        ResourceRepository $resourceRepository
    ): Post|Comment|User|Message|Article|Resource|null {
        return match ($targetType) {
            Report::TARGET_POST => $postRepository->find($targetId),
            Report::TARGET_COMMENT => $commentRepository->find($targetId),
            Report::TARGET_PROFILE => $userRepository->find($targetId),
            Report::TARGET_MESSAGE => $messageRepository->find($targetId),
            Report::TARGET_ARTICLE => $articleRepository->find($targetId),
            Report::TARGET_RESOURCE => $resourceRepository->find($targetId),
            default => null,
        };
    }

    private function isOwnTarget(
        User $currentUser,
        string $targetType,
        int $targetId,
        object $target
    ): bool {
        return match ($targetType) {
            Report::TARGET_POST => ($target instanceof Post && $target->getUser()?->getId() === $currentUser->getId()),
            Report::TARGET_COMMENT => ($target instanceof Comment && $target->getUser()?->getId() === $currentUser->getId()),
            Report::TARGET_PROFILE => ($targetId === $currentUser->getId()),
            Report::TARGET_MESSAGE => ($target instanceof Message && $target->getSender()?->getId() === $currentUser->getId()),
            Report::TARGET_ARTICLE => ($target instanceof Article && $target->getUser()?->getId() === $currentUser->getId()),
            Report::TARGET_RESOURCE => ($target instanceof Resource && $target->getUser()?->getId() === $currentUser->getId()),
            default => false,
        };
    }

    private function resolveReason(array $data): ?string
    {
        $reasonCode = strtolower(trim((string) ($data['reasonCode'] ?? '')));
        $customReason = trim((string) ($data['customReason'] ?? ''));

        if ($reasonCode !== '') {
            if ($reasonCode === 'other') {
                return $customReason !== '' ? $customReason : null;
            }

            return self::REASON_LABELS[$reasonCode] ?? null;
        }

        // Backward compatibility with legacy clients that still send a free-text reason.
        $legacyReason = trim((string) ($data['reason'] ?? ''));
        if ($legacyReason === '') {
            return null;
        }

        $legacyKey = strtolower($legacyReason);
        $legacyAliases = [
            'spam' => self::REASON_LABELS['spam'],
            'harcelement' => self::REASON_LABELS['harassment'],
            'harcèlement' => self::REASON_LABELS['harassment'],
            'contenu inapproprie' => self::REASON_LABELS['inappropriate_content'],
            'contenu inapproprié' => self::REASON_LABELS['inappropriate_content'],
            "usurpation d'identite" => self::REASON_LABELS['impersonation'],
            "usurpation d'identité" => self::REASON_LABELS['impersonation'],
            'autre' => self::REASON_LABELS['other'],
        ];

        return $legacyAliases[$legacyKey] ?? $legacyReason;
    }
}
