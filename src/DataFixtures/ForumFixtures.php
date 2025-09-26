<?php

namespace App\DataFixtures;

use App\Entity\Forum;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class ForumFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        // $user = $this->getReference('user1', User::class);
        $forumNames = [
            'Divers',
            'Administratif',
            'Méthodologie quantitative',
            'Méthodologie qualitative',
            'Méthodologie mixte',
            'Auteurs',
            'Oeuvres',
            'Détente'
        ];

        // Create 10 forums with a reference to the user
        foreach ($forumNames as $i => $forumName) {
            $forum = new Forum();
            $forum->setTitle("$forumName")
                  ->setBody("This is forum number $i.")
                  ->setLastActivity(new \DateTime());

            $manager->persist($forum);

            $this->addReference("forum" . ($i + 1), $forum);
        }
        $manager->flush();

        // Créer un forum "le café des lumières" avec une description spécifique, anonyme et debussy_clairDeLune à true
        $specialForum = new Forum();
        $specialForum->setTitle("Le café des lumières")
                       ->setBody("Bienvenue au café des lumières, un espace anonyme ou l'insulte est permise avec modération. N'hésitez pas a dire ce que vous avez sur le coeur sous ce fond de musique classique (évidement tout dire raciste, lgbtphobe, et tout le ce qui finit par phobie en général est prohibé).")
                       ->setAnonymous(true)
                       ->setDebussyClairDeLune(true)
                       ->setLastActivity(new \DateTime());
        $manager->persist($specialForum);
        $this->addReference("forum_special", $specialForum);
        $manager->flush();

    }
}
