<?php

namespace App\DataFixtures;

use App\Entity\User;
use App\Entity\UserQuestions;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class UserQuestionsFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $user = $this->getReference('user1', User::class);

        for ($i = 1; $i <= 10; $i++) {
            $userQuestion = new UserQuestions();
            $userQuestion->setUser($user);
            $userQuestion->setQuestion("Question $i");
            $userQuestion->setAnswer("Answer for question $i");

            $manager->persist($userQuestion);
        }

        $manager->flush();
    }
}
