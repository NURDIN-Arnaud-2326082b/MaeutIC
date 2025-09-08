<?php

namespace App\DataFixtures;

use App\Entity\User;
use App\Entity\UserLike;
use App\Entity\Comment;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;

class LikeFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $user1 = $this->getReference('user1', User::class);
        $user2 = $this->getReference('user2', User::class);

        for ($i = 0; $i < 8; $i++) {
            // Get the comment reference
            $comment = $this->getReference("comment" . ($i + 1), Comment::class);

            // Create a user like for each comment
            $userLike = new UserLike();
            $userLike->setUser($user2)
                ->setComment($comment);

            $manager->persist($userLike);
        }

        for ($i = 0; $i < 3; $i++) {
            // Get the comment reference
            $comment = $this->getReference("comment" . (($i + 1)*2), Comment::class);

            // Create a user like for each comment
            $userLike = new UserLike();
            $userLike->setUser($user1)
                ->setComment($comment);

            $manager->persist($userLike);
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            UserFixtures::class,
            CommentFixtures::class,
        ];
    }
}
