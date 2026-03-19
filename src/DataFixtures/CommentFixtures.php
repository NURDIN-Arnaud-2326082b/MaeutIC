<?php

namespace App\DataFixtures;

use App\Entity\Comment;
use App\Entity\User;
use App\Entity\Post;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Faker\Factory;

class CommentFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create('fr_FR');

        for ($i = 0; $i < 8; $i++) {
            for ($j = 1; $j <= 5; $j++) {
                $postRef = "post_" . $i . "_" . $j;

                // CORRECTION ICI : Ajout de Post::class
                if (!$this->hasReference($postRef, Post::class)) continue;

                /** @var Post $post */
                $post = $this->getReference($postRef, Post::class);

                $numComments = $faker->numberBetween(0, 6);

                for ($k = 1; $k <= $numComments; $k++) {
                    $randomUserId = $faker->numberBetween(1, 10);
                    /** @var User $user */
                    $user = $this->getReference("user" . $randomUserId, User::class);

                    $comment = new Comment();
                    $comment->setBody($faker->realText($faker->numberBetween(50, 300)))
                        ->setCreationDate($faker->dateTimeBetween($post->getCreationDate(), 'now'))
                        ->setUser($user)
                        ->setPost($post);

                    $manager->persist($comment);

                    if ($i === 0 && $k === 1) {
                        $this->addReference("comment" . $j, $comment);
                    }
                }
            }
        }
        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            UserFixtures::class,
            PostFixtures::class,
        ];
    }
}