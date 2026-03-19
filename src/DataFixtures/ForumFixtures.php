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
        $forumsData = [
            ['title' => 'Divers', 'body' => 'Discussions générales, actualités de la recherche et échanges informels entre membres du réseau.', 'special' => 'general'],
            ['title' => 'Administratif', 'body' => 'Entraide sur les démarches, financements, bourses, contrats doctoraux et qualification.', 'special' => 'administratif'],
            ['title' => 'Méthodologie quantitative', 'body' => 'Questions sur les statistiques, R, Python, SPSS, passation de questionnaires et échantillonnage.', 'special' => 'methodology'],
            ['title' => 'Méthodologie qualitative', 'body' => 'Échanges autour des grilles d\'entretien, de l\'observation participante et des logiciels comme NVivo.', 'special' => 'methodology'],
            ['title' => 'Méthodologie mixte', 'body' => 'Comment trianguler vos données qualitatives et quantitatives efficacement ?', 'special' => 'methodology'],
            ['title' => 'Auteurs', 'body' => 'Débats et partages de fiches de lecture sur les grands théoriciens et auteurs contemporains.', 'special' => 'general'],
            ['title' => 'Oeuvres', 'body' => 'Recommandations d\'ouvrages, critiques littéraires et revues de littérature.', 'special' => 'general'],
            ['title' => 'Détente', 'body' => 'La pause café des chercheurs. Anecdotes de terrain, memes académiques et décompression.', 'special' => 'detente']
        ];

        foreach ($forumsData as $i => $data) {
            $forum = new Forum();
            $forum->setTitle($data['title'])
                ->setBody($data['body'])
                ->setLastActivity(new DateTime())
                ->setSpecial($data['special']); // On force la valeur (ex: 'general') au lieu de NULL

            $manager->persist($forum);
            $this->addReference("forum" . ($i + 1), $forum);
        }

        $manager->flush();
    }
}