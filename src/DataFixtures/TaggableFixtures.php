<?php

namespace App\DataFixtures;

use App\Entity\Taggable;
use App\Entity\Tag;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class TaggableFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $entityTypes = [
            'Article',
            'Book',
            'Resource',
        ];

        $tags = $manager->getRepository(Tag::class)->findAll();

        for ($i = 1; $i <= 10; $i++) {
            $taggable = new Taggable();
            $taggable->setEntityId($i)
                ->setEntityType($entityTypes[array_rand($entityTypes)])
                ->setTag($tags[array_rand($tags)]);
            $manager->persist($taggable);
        }

        $manager->flush();
    }
}
