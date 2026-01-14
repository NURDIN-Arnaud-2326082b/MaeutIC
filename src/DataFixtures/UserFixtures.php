<?php

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserFixtures extends Fixture
{
    private UserPasswordHasherInterface $passwordHasher;

    public function __construct(UserPasswordHasherInterface $passwordHasher)
    {
        $this->passwordHasher = $passwordHasher;
    }

    public function load(ObjectManager $manager): void
    {
        $user = new User();
        $user->setEmail('user@example.com')
            ->setUsername('user1')
            ->setPassword($this->passwordHasher->hashPassword($user, 'password'))
            ->setLastName('Doe')
            ->setFirstName('John')
            ->setAffiliationLocation('New York, USA')
            ->setSpecialization('Web Development')
            ->setResearchTopic('Symfony Framework')
            ->setUserType(0)
            ->setGenre('male');

        $user2 = new User();
        $user2->setEmail('user2@example.com')
            ->setUsername('user2')
            ->setPassword($this->passwordHasher->hashPassword($user, 'password'))
            ->setLastName('Doe')
            ->setFirstName('John')
            ->setAffiliationLocation('New York, USA')
            ->setSpecialization('Web Development')
            ->setResearchTopic('Symfony Framework')
            ->setUserType(1)
            ->setGenre('female');

        $manager->persist($user);
        $manager->persist($user2);
        $manager->flush();

        // Add reference for other fixtures
        $this->addReference('user1', $user);
        $this->addReference('user2', $user2);
    }
}
