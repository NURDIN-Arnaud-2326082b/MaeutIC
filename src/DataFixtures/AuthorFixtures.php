<?php

namespace App\DataFixtures;

use App\Entity\Author;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;

class AuthorFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {

        $nationalities = [
            'fr',
            'us',
            'at',
            'de',
            'it',
            'jm',
            'ru',
        ];

        $user1 = $this->getReference('user1', User::class);

        for ($i = 1; $i <= 10; $i++) {
            $author = new Author();
            $author->setName("Author $i")
                ->setBirthYear(1900 + $i)
                ->setDeathYear(2000 + $i)
                ->setNationality($nationalities[array_rand($nationalities)])
                ->setLink("https://example.com/author-$i")
                ->setImage("https://example.com/image-$i.jpg")
                ->setUser($user1);
            $manager->persist($author);
        }

        $author = new Author();
        $author->setName("Pierre Bourdieu")
            ->setBirthYear(1930)
            ->setDeathYear(2002)
            ->setNationality("fr")
            ->setLink("https://en.wikipedia.org/wiki/Pierre_Bourdieu")
            ->setImage("https://upload.wikimedia.org/wikipedia/commons/c/c0/Pierre_Bourdieu_%281%29.jpg")
            ->setUser($user1);

        $manager->persist($author);

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            UserFixtures::class,
        ];
    }
}
