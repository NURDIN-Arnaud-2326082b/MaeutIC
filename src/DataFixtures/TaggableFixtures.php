<?php

namespace App\DataFixtures;

use App\Entity\Taggable;
use App\Entity\Tag;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Faker\Factory;

class TaggableFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create();
        $entityTypes = ['Article', 'Book', 'Resource'];

        // On récupère tous les tags créés précédemment
        $tags = $manager->getRepository(Tag::class)->findAll();

        if (empty($tags)) return;

        for ($i = 1; $i <= 30; $i++) {
            $taggable = new Taggable();
            // On associe un tag au hasard à une entité au hasard (IDs 1 à 15 générés dans les autres fixtures)
            $taggable->setEntityId($faker->numberBetween(1, 15))
                ->setEntityType($faker->randomElement($entityTypes))
                ->setTag($faker->randomElement($tags));

            $manager->persist($taggable);
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [TagFixtures::class];
    }
}