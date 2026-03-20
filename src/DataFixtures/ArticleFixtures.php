<?php

namespace App\DataFixtures;

use App\Entity\Article;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Faker\Factory;

class ArticleFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create('fr_FR');

        $themes = ['La construction sociale de', 'Analyse comparative des', 'L\'impact du numérique sur', 'Repenser les dynamiques de', 'Approche critique de'];
        $sujets = ['la précarité étudiante', 'l\'intégration urbaine', 'la participation politique', 'l\'éducation inclusive', 'la transition écologique'];

        for ($i = 1; $i <= 15; $i++) {
            $randomUserId = $faker->numberBetween(1, 10);
            /** @var User $user */
            $user = $this->getReference("user" . $randomUserId, User::class);

            $titre = $faker->randomElement($themes) . ' ' . $faker->randomElement($sujets) . ' : ' . $faker->catchPhrase();

            $article = new Article();
            $article->setTitle($titre)
                ->setLink($faker->url())
                ->setUser($user);

            $manager->persist($article);
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [UserFixtures::class];
    }
}