<?php

namespace App\DataFixtures;

use App\Entity\Comment;
use App\Entity\Post;
use App\Entity\PostLike;
use App\Entity\User;
use App\Entity\UserLike;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class LikeFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $user1 = $this->getReference('user1', User::class);
        $user2 = $this->getReference('user2', User::class);

        for ($i = 0; $i < 8; $i++) {
            $comment = $this->getReference("comment" . ($i + 1), Comment::class);

            $userLike = new UserLike();
            $userLike->setUser($user2)
                ->setComment($comment);

            $manager->persist($userLike);
        }

        for ($i = 0; $i < 3; $i++) {
            $comment = $this->getReference("comment" . (($i + 1) * 2), Comment::class);

            $userLike = new UserLike();
            $userLike->setUser($user1)
                ->setComment($comment);

            $manager->persist($userLike);
        }

        // Ajouter des likes sur les posts
        for ($i = 1; $i <= 5; $i++) {
            if ($this->hasReference("post$i", Post::class)) {
                $post = $this->getReference("post$i", Post::class);

                // User2 like certains posts
                if ($i % 2 === 0) {
                    $postLike = new PostLike();
                    $postLike->setUser($user2);
                    $postLike->setPost($post);
                    $manager->persist($postLike);
                }

                // User1 like d'autres posts
                if ($i % 3 === 0) {
                    $postLike = new PostLike();
                    $postLike->setUser($user1);
                    $postLike->setPost($post);
                    $manager->persist($postLike);
                }
            }
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            UserFixtures::class,
            CommentFixtures::class,
            PostFixtures::class,
        ];
    }
}
