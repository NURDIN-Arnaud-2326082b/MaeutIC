<?php

namespace App\DataFixtures;

use App\Entity\Author;
use App\Entity\Book;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;

class BookFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create('fr_FR');

        for ($i = 1; $i <= 15; $i++) {
            /** @var User $user */
            $user = $this->getReference("user" . $faker->numberBetween(1, 10), User::class);

            $book = new Book();
            $book->setTitle(ucfirst($faker->words($faker->numberBetween(2, 5), true)))
//                ->setAuthor($faker->firstName() . ' ' . $faker->lastName())
                ->setIsbn($faker->isbn13())
                ->setImage("https://covers.openlibrary.org/b/id/" . $faker->numberBetween(8000000, 9000000) . "-M.jpg") // Vraies images de couvertures aléatoires
                ->setUser($user);

            $numAuthors = $faker->numberBetween(1, 2);
            for ($a = 0; $a < $numAuthors; $a++) {
                $randomAuthorId = $faker->numberBetween(1, 10);
                if ($this->hasReference("author" . $randomAuthorId, Author::class)) {
                    $author = $this->getReference("author" . $randomAuthorId, Author::class);
                    $book->addAuthor($author);
                }
            }

            $manager->persist($book);
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [UserFixtures::class];
    }
}