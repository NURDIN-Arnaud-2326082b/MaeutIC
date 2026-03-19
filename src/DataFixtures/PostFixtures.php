<?php

namespace App\DataFixtures;

use App\Entity\Post;
use App\Entity\User;
use App\Entity\Forum;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Faker\Factory;

class PostFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create('fr_FR');

        for ($i = 0; $i < 8; $i++) {
            // CORRECTION ICI : Ajout de Forum::class
            if (!$this->hasReference("forum" . ($i + 1), Forum::class)) continue;

            /** @var Forum $forum */
            $forum = $this->getReference("forum" . ($i + 1), Forum::class);

            $numPosts = $faker->numberBetween(5, 8);

            for ($j = 1; $j <= $numPosts; $j++) {
                $randomUserId = $faker->numberBetween(1, 10);
                /** @var User $user */
                $user = $this->getReference("user" . $randomUserId, User::class);

                $post = new Post();
                $post->setName(rtrim($faker->sentence($faker->numberBetween(4, 8)), '.') . ' ?')
                    ->setDescription($faker->paragraphs($faker->numberBetween(2, 4), true))
                    ->setUser($user)
                    ->setCreationDate($faker->dateTimeBetween('-6 months', '-1 months'))
                    ->setLastActivity($faker->dateTimeBetween('-1 months', 'now'))
                    ->setForum($forum);

                $manager->persist($post);

                if ($j <= 5) {
                    $this->addReference("post_" . $i . "_" . $j, $post);
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