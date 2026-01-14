<?php

namespace App\DataFixtures;

use App\Entity\Comment;
use App\Entity\User;
use App\Entity\Post;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;


class CommentFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $user = $this->getReference('user1', User::class);

        for ($i = 0; $i < 8; $i++) {
            // Get the poste reference
            $post = $this->getReference("post" . ($i + 1), Post::class);

            // Create 5 comments for each post
            for ($j = 1; $j <= 5; $j++) {
                $comment = new Comment();
                $comment->setBody("This is comment number $j for post " . ($i + 1) . ". je m'appelle ". $user->getUsername() ." et j'adore commenter.")
                        ->setCreationDate(new \DateTime())
                        ->setUser($user)
                        ->setPost($post);

                $manager->persist($comment);

                // Add a reference for the first comments of each posts
                if ($j === 1) {
                    $this->addReference("comment" . ($i + 1), $comment);
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