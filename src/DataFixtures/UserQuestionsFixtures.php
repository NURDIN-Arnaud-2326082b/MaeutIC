<?php

namespace App\DataFixtures;

use App\Entity\UserQuestions;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Faker\Factory;

class UserQuestionsFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create('fr_FR');

        $methodologies = [
            'Approche mixte, alliant entretiens semi-directifs et analyse de données quantitatives.',
            'Ethnographie de longue durée et observation participante.',
            'Recherche-action participative et ateliers coopératifs avec les acteurs locaux.',
            'Analyse de discours, sémiologie et fouille de textes (text mining).',
            'Études de cas multiples et modélisation systémique.'
        ];

        $auteurs = [
            'Pierre Bourdieu, Michel Foucault, Bruno Latour, Hannah Arendt',
            'Erving Goffman, Howard Becker, Anselm Strauss, Robert Park',
            'Max Weber, Karl Marx, Émile Durkheim, Georg Simmel',
            'Judith Butler, Simone de Beauvoir, Donna Haraway, Nancy Fraser'
        ];

        $citations = [
            '"Le savant n\'est pas l\'homme qui fournit les vraies réponses, c\'est celui qui pose les vraies questions." - Claude Lévi-Strauss',
            '"Rien ne va de soi. Rien n\'est donné. Tout est construit." - Gaston Bachelard',
            '"Le but de la sociologie est de découvrir des nécessités." - Pierre Bourdieu',
            '"L\'histoire est une galerie de tableaux où il y a peu d\'originaux et beaucoup de copies." - Alexis de Tocqueville'
        ];

        $motsCles = ['Sociologie', 'Anthropologie', 'IA', 'Écologie', 'Politiques publiques', 'Genre', 'Inégalités', 'Travail', 'Santé', 'Éducation', 'Migration', 'Ville', 'Numérique'];

        for ($i = 1; $i <= 10; $i++) {
            // CORRECTION ICI : Ajout de User::class
            if (!$this->hasReference('user' . $i, User::class)) continue;

            /** @var User $user */
            $user = $this->getReference('user' . $i, User::class);

            $uq1 = new UserQuestions();
            $uq1->setUser($user)->setQuestion('Méthodologies')->setAnswer($faker->randomElement($methodologies));
            $manager->persist($uq1);

            $uq2 = new UserQuestions();
            $uq2->setUser($user)->setQuestion('Auteurs marquants')->setAnswer($faker->randomElement($auteurs));
            $manager->persist($uq2);

            $uq3 = new UserQuestions();
            $uq3->setUser($user)->setQuestion('Citation')->setAnswer($faker->randomElement($citations));
            $manager->persist($uq3);

            $tagsChoisis = $faker->randomElements($motsCles, $faker->numberBetween(2, 4));
            foreach ($tagsChoisis as $tag) {
                $uqTag = new UserQuestions();
                $uqTag->setUser($user)->setQuestion('Taggable Question 0')->setAnswer($tag);
                $manager->persist($uqTag);
            }

            $uq4 = new UserQuestions();
            $uq4->setUser($user)->setQuestion('Question 2')->setAnswer($faker->realText(150));
            $manager->persist($uq4);
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