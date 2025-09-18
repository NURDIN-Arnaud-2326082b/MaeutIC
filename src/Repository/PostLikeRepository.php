<?php

namespace App\Repository;

use App\Entity\PostLike;
use App\Entity\Post;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class PostLikeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PostLike::class);
    }

    public function findByUserAndPost(User $user, Post $post): ?PostLike
    {
        return $this->findOneBy([
            'user' => $user,
            'post' => $post
        ]);
    }

    public function countByPost(Post $post): int
    {
        return $this->count(['post' => $post]);
    }

    public function isLikedByUser(Post $post, User $user): bool
    {
        return $this->findByUserAndPost($user, $post) !== null;
    }
}
