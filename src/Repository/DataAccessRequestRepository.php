<?php

namespace App\Repository;

use App\Entity\DataAccessRequest;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class DataAccessRequestRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DataAccessRequest::class);
    }

    public function findPendingByRequester(User $requester): ?DataAccessRequest
    {
        return $this->findOneBy([
            'requester' => $requester,
            'status' => DataAccessRequest::STATUS_PENDING,
        ]);
    }
}
