<?php

namespace App\DataFixtures;

use App\Entity\Book;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;

class BookFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $user1 = $this->getReference('user1', User::class);

        for ($i = 1; $i <= 10; $i++) {
            $book = new Book();
            $book->setTitle("Book Title $i")
                ->setAuthor("Author $i")
                ->setLink("https://example.com/book-$i")
                ->setImage("https://example.com/image-$i.jpg")
                ->setUser($user1);
            $manager->persist($book);
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            UserFixtures::class,
        ];
    }
}