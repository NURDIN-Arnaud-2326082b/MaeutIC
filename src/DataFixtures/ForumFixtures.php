<?php

namespace App\DataFixtures;

use App\Entity\Forum;
use DateTime;
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

        $metho_forum = [
            'Méthodologie quantitative',
            'Méthodologie qualitative',
            'Méthodologie mixte'
        ];

        $detente_forum = [
            'Détente'
        ];

        $admin_forum = [
            'Administratif'
        ];

        // Create 10 forums with a reference to the user
        foreach ($forumNames as $i => $forumName) {
            $forum = new Forum();
            $forum->setTitle("$forumName")
                ->setBody("This is forum number $i.")
                ->setLastActivity(new DateTime());

            $manager->persist($forum);

            $this->addReference("forum" . ($i + 1), $forum);
        }
        $manager->flush();

        // Donner aux forums methodologies la spécialité correspondante
        foreach ($metho_forum as $methodologyName) {
            $forum = $manager->getRepository(Forum::class)->findOneBy(['title' => $methodologyName]);
            if ($forum) {
                $forum->setSpecial('methodology');
                $manager->persist($forum);
            }
        }
        $manager->flush();

        // Donner au forum détente la spécialité correspondante
        foreach ($detente_forum as $detenteName) {
            $forum = $manager->getRepository(Forum::class)->findOneBy(['title' => $detenteName]);
            if ($forum) {
                $forum->setSpecial('detente');
                $manager->persist($forum);
            }
        }
        $manager->flush();

        // Donner au forum administratif la spécialité correspondante
        foreach ($admin_forum as $adminName) {
            $forum = $manager->getRepository(Forum::class)->findOneBy(['title' => $adminName]);
            if ($forum) {
                $forum->setSpecial('administratif');
                $manager->persist($forum);
            }
        }
        $manager->flush();

        // Créer un forum "le café des lumières" avec une description spécifique, anonyme et debussy_clairDeLune à true
        $specialForum = new Forum();
        $specialForum->setTitle("Le café des lumières")
            ->setBody("Bienvenue au café des lumières, un espace anonyme ou l'insulte est permise avec modération. N'hésitez pas a dire ce que vous avez sur le coeur sous ce fond de musique classique (évidement tout dire raciste, lgbtphobe, et tout le ce qui finit par phobie en général est prohibé).")
            ->setAnonymous(true)
            ->setDebussyClairDeLune(true)
            ->setLastActivity(new DateTime())
            ->setSpecial("cafe_des_lumieres");

        $manager->persist($specialForum);
        $this->addReference("forum_special", $specialForum);
        $manager->flush();

    }
}
