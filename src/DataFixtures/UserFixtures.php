<?php

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Faker\Factory;

class UserFixtures extends Fixture
{
    private UserPasswordHasherInterface $passwordHasher;

    public function __construct(UserPasswordHasherInterface $passwordHasher)
    {
        $this->passwordHasher = $passwordHasher;
    }

    public function load(ObjectManager $manager): void
    {
        // Initialiser Faker en français
        $faker = Factory::create('fr_FR');

        // Quelques spécialités et sujets réalistes pour la génération
        $specializations = ['Sociologie', 'Anthropologie', 'Psychologie sociale', 'Histoire contemporaine', 'Sciences de l\'éducation', 'Philosophie', 'Sciences politiques'];

        // 1. On crée un utilisateur de test "fixe" pour que tu puisses te connecter facilement
        $testUser = new User();
        $testUser->setEmail('test@chercheur.fr')
            ->setUsername('jean_dupont')
            ->setPassword($this->passwordHasher->hashPassword($testUser, 'password123!'))
            ->setLastName('Dupont')
            ->setFirstName('Jean')
            ->setAffiliationLocation('Université Paris-Saclay')
            ->setSpecialization('Sociologie')
            ->setResearchTopic('L\'impact du numérique sur les dynamiques de groupe')
            ->setUserType(0)
            ->setGenre('homme');

        $manager->persist($testUser);
        $this->addReference('user1', $testUser); // On garde 'user1' pour ne pas casser tes autres fixtures

        // 2. On génère 9 autres chercheurs très réalistes
        for ($i = 2; $i <= 10; $i++) {
            $user = new User();

            // Un peu de logique pour accorder le genre et les prénoms
            $genre = $faker->randomElement(['homme', 'femme']);
            $firstName = $genre === 'homme' ? $faker->firstNameMale() : $faker->firstNameFemale();
            $lastName = $faker->lastName();

            $specialty = $faker->randomElement($specializations);

            // Générer un nom d'utilisateur crédible (ex: marie.laurent89)
            $username = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $firstName . '.' . $lastName)) . $faker->numberBetween(10, 99);

            $user->setEmail($faker->unique()->safeEmail())
                ->setUsername($username)
                ->setPassword($this->passwordHasher->hashPassword($user, 'password'))
                ->setLastName($lastName)
                ->setFirstName($firstName)
                ->setAffiliationLocation($faker->company() . ' - ' . $faker->city())
                ->setSpecialization($specialty)
                ->setResearchTopic($faker->catchPhrase()) // Catchphrase génère des phrases qui font très "titre de thèse"
                ->setUserType($faker->randomElement([0, 1]))
                ->setGenre($genre);

            $manager->persist($user);
            $this->addReference('user' . $i, $user);
        }

        $manager->flush();
    }
}