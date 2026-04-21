<?php

namespace App\EventListener;

use App\Entity\Article;
use App\Entity\Comment;
use App\Entity\Message;
use App\Entity\Post;
use App\Entity\Report;
use App\Entity\Resource;
use App\Entity\User;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Events;

class ReportTargetCleanupSubscriber implements EventSubscriber
{
    public function getSubscribedEvents(): array
    {
        return [Events::onFlush];
    }

    public function onFlush(OnFlushEventArgs $args): void
    {
        $entityManager = $args->getObjectManager();
        if (!$entityManager instanceof EntityManagerInterface) {
            return;
        }

        $unitOfWork = $entityManager->getUnitOfWork();
        $scheduledDeletions = $unitOfWork->getScheduledEntityDeletions();

        if (empty($scheduledDeletions)) {
            return;
        }

        $targetsToCleanup = [];
        foreach ($scheduledDeletions as $entity) {
            [$targetType, $targetId] = $this->resolveReportTarget($entity);
            if ($targetType === null || $targetId === null) {
                continue;
            }

            $key = sprintf('%s:%d', $targetType, $targetId);
            $targetsToCleanup[$key] = [$targetType, $targetId];
        }

        if (empty($targetsToCleanup)) {
            return;
        }

        $connection = $entityManager->getConnection();
        foreach ($targetsToCleanup as [$targetType, $targetId]) {
            $connection->executeStatement(
                'DELETE FROM report WHERE target_type = :targetType AND target_id = :targetId',
                [
                    'targetType' => $targetType,
                    'targetId' => $targetId,
                ]
            );
        }
    }

    /**
     * @return array{0: string|null, 1: int|null}
     */
    private function resolveReportTarget(object $entity): array
    {
        if ($entity instanceof Post) {
            return [Report::TARGET_POST, $entity->getId()];
        }

        if ($entity instanceof Comment) {
            return [Report::TARGET_COMMENT, $entity->getId()];
        }

        if ($entity instanceof Message) {
            return [Report::TARGET_MESSAGE, $entity->getId()];
        }

        if ($entity instanceof User) {
            return [Report::TARGET_PROFILE, $entity->getId()];
        }

        if ($entity instanceof Article) {
            return [Report::TARGET_ARTICLE, $entity->getId()];
        }

        if ($entity instanceof Resource) {
            return [Report::TARGET_RESOURCE, $entity->getId()];
        }

        return [null, null];
    }
}
