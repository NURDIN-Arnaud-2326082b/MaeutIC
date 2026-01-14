<?php

namespace App\Repository;

use App\Entity\Notification;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class NotificationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Notification::class);
    }

    /**
     * Retourne les notifications 'pending' pour un destinataire donné, ordonnées par date.
     *
     * @param User $user
     * @return Notification[]
     */
    public function findPendingByRecipient(User $user): array
    {
        return $this->createQueryBuilder('n')
            ->andWhere('n.recipient = :user')
            ->andWhere('n.status = :status')
            ->setParameter('user', $user)
            ->setParameter('status', 'pending')
            ->orderBy('n.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
