<?php

namespace App\DataFixtures;

use App\Entity\Tag;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class TagFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $motsCles = [
            'Sociologie', 'Anthropologie', 'IA', 'Écologie', 'Politiques publiques',
            'Genre', 'Inégalités', 'Travail', 'Santé', 'Éducation', 'Migration',
            'Ville', 'Numérique', 'Qualitatif', 'Quantitatif', 'Épistémologie'
        ];

        foreach ($motsCles as $i => $mot) {
            $tag = new Tag();
            $tag->setName($mot);
            $manager->persist($tag);

            // On garde la référence pour TaggableFixtures
            $this->addReference("tag_" . $i, $tag);
        }

        $manager->flush();
    }
}