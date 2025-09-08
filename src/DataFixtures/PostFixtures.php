<?php

namespace App\DataFixtures;

use App\Entity\Post;
use App\Entity\User;
use App\Entity\Forum;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;

class PostFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $user = $this->getReference('user1', User::class);

        // Loop through forums and create 5 posts for each
        for ($i = 0; $i < 8; $i++) {
            $forum = $this->getReference("forum" . ($i + 1), Forum::class);

            for ($j = 1; $j <= 5; $j++) {
                $post = new Post();
                $post->setName("Post Title $j for Forum $i")
                     ->setDescription("This is the body of post $j in forum $i.")
                     ->setUser($user)
                     ->setCreationDate(new \DateTime())
                     ->setLastActivity(new \DateTime())
                     ->setForum($forum);

                $manager->persist($post);

                // Add a reference for the first post of each forum
                if ($j === 1) {
                    $this->addReference("post" . ($i + 1), $post);
                }
            }
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            UserFixtures::class,
            ForumFixtures::class,
        ];
    }
}
