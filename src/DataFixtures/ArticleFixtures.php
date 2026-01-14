<?php

namespace App\DataFixtures;

use App\Entity\Article;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;

class ArticleFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $user1 = $this->getReference('user1', User::class);

        for ($i = 1; $i <= 10; $i++) {
            $article = new Article();
            $article->setTitle("Article Title $i")
                ->setAuthor("Author $i")
                ->setLink("https://example.com/article-$i")
                ->setUser($user1);
            $manager->persist($article);
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
