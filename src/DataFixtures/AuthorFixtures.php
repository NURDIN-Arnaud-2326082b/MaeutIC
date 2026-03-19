<?php

namespace App\DataFixtures;

use App\Entity\Author;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;

class AuthorFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create('fr_FR');
        $nationalities = ['fr', 'us', 'at', 'de', 'it', 'uk'];

        // On assigne au hasard ces créations à nos utilisateurs
        $getRandomUser = fn() => $this->getReference("user" . $faker->numberBetween(1, 10), User::class);

        // Quelques vrais auteurs classiques
        $realAuthors = [
            ['name' => 'Pierre Bourdieu', 'birth' => 1930, 'death' => 2002, 'nat' => 'fr', 'link' => 'https://fr.wikipedia.org/wiki/Pierre_Bourdieu'],
            ['name' => 'Michel Foucault', 'birth' => 1926, 'death' => 1984, 'nat' => 'fr', 'link' => 'https://fr.wikipedia.org/wiki/Michel_Foucault'],
            ['name' => 'Hannah Arendt', 'birth' => 1906, 'death' => 1975, 'nat' => 'de', 'link' => 'https://fr.wikipedia.org/wiki/Hannah_Arendt'],
            ['name' => 'Erving Goffman', 'birth' => 1922, 'death' => 1982, 'nat' => 'us', 'link' => 'https://fr.wikipedia.org/wiki/Erving_Goffman']
        ];

        foreach ($realAuthors as $ra) {
            $author = new Author();
            $author->setName($ra['name'])
                ->setBirthYear($ra['birth'])
                ->setDeathYear($ra['death'])
                ->setNationality($ra['nat'])
                ->setLink($ra['link'])
                ->setImage("https://ui-avatars.com/api/?name=" . urlencode($ra['name']) . "&background=random")
                ->setUser($getRandomUser());
            $manager->persist($author);

            $this->addReference("author" . $ra['name'], $author);
        }

        // Quelques auteurs fictifs/générés
        for ($i = 1; $i <= 10; $i++) {
            $birth = $faker->numberBetween(1850, 1950);
            $author = new Author();
            $author->setName($faker->firstName() . ' ' . $faker->lastName())
                ->setBirthYear($birth)
                ->setDeathYear($birth + $faker->numberBetween(40, 90))
                ->setNationality($faker->randomElement($nationalities))
                ->setLink($faker->url())
                ->setImage($faker->imageUrl(200, 200, 'people'))
                ->setUser($getRandomUser());
            $manager->persist($author);

            $this->addReference("author" . $i, $author);
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [UserFixtures::class];
    }
}