<?php

namespace App\DataFixtures;

use App\Entity\User;
use App\Entity\UserLike;
use App\Entity\PostLike;
use App\Entity\Comment;
use App\Entity\Post;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Faker\Factory;

class LikeFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create();

        // LIKES SUR LES COMMENTAIRES
        for ($i = 1; $i <= 5; $i++) {
            // CORRECTION ICI : Ajout de Comment::class
            if (!$this->hasReference("comment" . $i, Comment::class)) continue;

            $comment = $this->getReference("comment" . $i, Comment::class);

            $likersCount = $faker->numberBetween(0, 4);
            $alreadyLiked = [];

            for ($k = 0; $k < $likersCount; $k++) {
                $userId = $faker->numberBetween(1, 10);
                if (in_array($userId, $alreadyLiked)) continue;

                $user = $this->getReference("user" . $userId, User::class);

                $userLike = new UserLike();
                $userLike->setUser($user)->setComment($comment);
                $manager->persist($userLike);

                $alreadyLiked[] = $userId;
            }
        }

        // LIKES SUR LES POSTS
        for ($i = 0; $i < 8; $i++) {
            for ($j = 1; $j <= 5; $j++) {
                $postRef = "post_" . $i . "_" . $j;

                // CORRECTION ICI : Ajout de Post::class
                if (!$this->hasReference($postRef, Post::class)) continue;

                $post = $this->getReference($postRef, Post::class);

                $likersCount = $faker->numberBetween(1, 7);
                $alreadyLiked = [];

                for ($k = 0; $k < $likersCount; $k++) {
                    $userId = $faker->numberBetween(1, 10);
                    if (in_array($userId, $alreadyLiked)) continue;

                    $user = $this->getReference("user" . $userId, User::class);

                    $postLike = new PostLike();
                    $postLike->setUser($user)->setPost($post);
                    $postLike->setCreatedAt(new \DateTimeImmutable());
                    $manager->persist($postLike);

                    $alreadyLiked[] = $userId;
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