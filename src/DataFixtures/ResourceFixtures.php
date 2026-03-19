<?php

namespace App\DataFixtures;

use App\Entity\Resource;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Faker\Factory;

class ResourceFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create('fr_FR');
        $pageTypes = ['chill', 'methodology', 'administrative'];

        // Quelques vraies ressources
        $realResources = [
            ['title' => 'Zotero - Gestionnaire de bibliographie', 'desc' => 'L\'outil indispensable pour gérer vos références et générer votre biblio automatiquement.', 'link' => 'https://www.zotero.org/', 'type' => 'methodology'],
            ['title' => 'Guide du doctorant', 'desc' => 'Toutes les démarches administratives expliquées pas à pas.', 'link' => 'https://www.andes.asso.fr/', 'type' => 'administrative'],
            ['title' => 'Lofi Girl - Radio pour se concentrer', 'desc' => 'Parfait pour les longues sessions de rédaction.', 'link' => 'https://www.youtube.com/watch?v=jfKfPfyJRdk', 'type' => 'chill']
        ];

        foreach ($realResources as $rr) {
            $resource = new Resource();
            $resource->setTitle($rr['title'])
                ->setDescription($rr['desc'])
                ->setLink($rr['link'])
                ->setPage($rr['type'])
                ->setUser($this->getReference("user" . $faker->numberBetween(1, 10), User::class));
            $manager->persist($resource);
        }

        // Quelques ressources générées
        for ($i = 1; $i <= 7; $i++) {
            $resource = new Resource();
            $resource->setTitle("Outil : " . ucfirst($faker->word()))
                ->setDescription($faker->paragraph(2))
                ->setLink($faker->url())
                ->setPage($faker->randomElement($pageTypes))
                ->setUser($this->getReference("user" . $faker->numberBetween(1, 10), User::class));
            $manager->persist($resource);
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [UserFixtures::class];
    }
}