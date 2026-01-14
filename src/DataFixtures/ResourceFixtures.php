<?php

namespace App\DataFixtures;

use App\Entity\Resource;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class ResourceFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $pageTypes = [
            'chill',
            'methodology',
            'administrative',
        ];

        $user = $this->getReference('user1', User::class);

        for ($i = 1; $i <= 10; $i++) {
            $resource = new Resource();
            $resource->setTitle("Resource Title $i")
                ->setDescription("Description for resource $i")
                ->setLink("https://example.com/resource-$i")
                ->setPage($pageTypes[array_rand($pageTypes)])
                ->setUser($user);
            $manager->persist($resource);
        }

        $resource = new Resource();
        $resource->setTitle("Resource Title $i")
            ->setDescription("Description for resource $i")
            ->setLink("https://www.youtube.com/watch?v=OMo5Y_AERPg")
            ->setPage($pageTypes[array_rand($pageTypes)])
            ->setUser($user);
        $manager->persist($resource);

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            UserFixtures::class,
        ];
    }
}
