<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\UserQuestions;
use App\Repository\TagRepository;
use App\Repository\UserQuestionsRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\AsciiSlugger;

#[Route('/api')]
class ProfileApiController extends AbstractController
{
    /**
     * Récupère les données de profil de l'utilisateur connecté pour l'édition
     */
    #[Route('/user/edit-data', name: 'api_profile_edit_data', methods: ['GET'])]
    public function getEditData(
        UserQuestionsRepository $userQuestionsRepository,
        TagRepository $tagRepository
    ): JsonResponse
    {
        /** @var User|null $user */
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['error' => 'Non authentifié'], 401);
        }

        // Questions dynamiques
        $dynamicQuestions = [
            'Pourquoi cette thématique de recherche vous intéresse-t-elle ?',
            'Pourquoi avez-vous souhaité être chercheur ?',
            'Qu\'aimez-vous dans la recherche ?',
            'Quels sont les problèmes de recherche auxquels vous vous intéressez ?',
            'Quelles sont les méthodologies de recherche que vous utilisez dans votre domaine d\'étude ?',
            'Qu\'est-ce qui, d\'après vous, vous a amené(e) à faire de la recherche ?',
            'Comment vous définiriez-vous en tant que chercheur ?',
            'Pensez-vous que ce choix ait un lien avec un évènement de votre biographie ?',
            'Pouvez-vous nous raconter ce qui a motivé le choix de vos thématiques de recherche ?',
            'Comment vos expériences personnelles ont-elles influencé votre choix de carrière et vos recherches en sciences humaines et sociales ?',
            'En quelques mots, en tant que chercheur(se) qu\'est-ce qui vous anime ?',
            'Si vous deviez choisir 4 auteurs qui vous ont marquée, quels seraient-ils ?',
            'Quelle est la phrase ou la citation qui vous représente le mieux ?',
        ];

        // Questions taggables
        $taggableQuestions = [
            'Quels mot-clés peuvent être reliés à votre projet en cours ?',
            'Si vous deviez choisir 5 mots pour vous définir en tant que chercheur(se), quels seraient-ils ?'
        ];

        // Récupérer les réponses existantes
        $userQuestions = $userQuestionsRepository->findAllByUser($user->getId());
        $userQuestionsData = [];
        $taggableQuestionsData = [[], []];

        foreach ($userQuestions as $uq) {
            if (str_starts_with($uq->getQuestion(), 'Taggable')) {
                $index = (int)filter_var($uq->getQuestion(), FILTER_SANITIZE_NUMBER_INT);
                $tag = $tagRepository->findOneBy(['name' => $uq->getAnswer()]);
                if ($tag) {
                    $taggableQuestionsData[$index][] = [
                        'id' => $tag->getId(),
                        'name' => $tag->getName()
                    ];
                }
            } else {
                $index = (int)filter_var($uq->getQuestion(), FILTER_SANITIZE_NUMBER_INT);
                $userQuestionsData[$index] = $uq->getAnswer();
            }
        }

        // S'assurer que toutes les questions sont présentes
        foreach ($dynamicQuestions as $i => $q) {
            if (!array_key_exists($i, $userQuestionsData)) {
                $userQuestionsData[$i] = '';
            }
        }

        return $this->json([
            'user' => [
                'email' => $user->getEmail(),
                'lastName' => $user->getLastName(),
                'firstName' => $user->getFirstName(),
                'username' => $user->getUsername(),
                'affiliationLocation' => $user->getAffiliationLocation(),
                'specialization' => $user->getSpecialization(),
                'researchTopic' => $user->getResearchTopic(),
                'profileImage' => $user->getProfileImage(),
            ],
            'dynamicQuestions' => $dynamicQuestions,
            'taggableQuestions' => $taggableQuestions,
            'userQuestionsAnswers' => $userQuestionsData,
            'taggableQuestionsAnswers' => $taggableQuestionsData,
        ]);
    }

    /**
     * Met à jour le profil de l'utilisateur connecté
     */
    #[Route('/user/update', name: 'api_profile_update', methods: ['POST'])]
    public function updateProfile(
        Request $request,
        EntityManagerInterface $entityManager,
        UserQuestionsRepository $userQuestionsRepository,
        TagRepository $tagRepository
    ): JsonResponse
    {
        /** @var User|null $user */
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['error' => 'Non authentifié'], 401);
        }

        // Gérer l'upload de fichier
        $profileImageFile = $request->files->get('profileImage');
        if ($profileImageFile) {
            $originalFilename = pathinfo($profileImageFile->getClientOriginalName(), PATHINFO_FILENAME);
            $safeFilename = (new AsciiSlugger())->slug($originalFilename);
            $newFilename = $safeFilename . '-' . uniqid() . '.' . $profileImageFile->guessExtension();

            try {
                $profileImageFile->move(
                    $this->getParameter('kernel.project_dir') . '/public/profile_images',
                    $newFilename
                );
                $user->setProfileImage($newFilename);
            } catch (FileException $e) {
                return $this->json(['error' => 'Erreur lors de l\'upload de la photo'], 500);
            }
        }

        // Mettre à jour les infos de base
        $data = json_decode($request->request->get('data'), true);
        
        if (isset($data['email'])) $user->setEmail($data['email']);
        if (isset($data['lastName'])) $user->setLastName($data['lastName']);
        if (isset($data['firstName'])) $user->setFirstName($data['firstName']);
        if (isset($data['username'])) $user->setUsername($data['username']);
        if (isset($data['affiliationLocation'])) $user->setAffiliationLocation($data['affiliationLocation']);
        if (isset($data['specialization'])) $user->setSpecialization($data['specialization']);
        if (isset($data['researchTopic'])) $user->setResearchTopic($data['researchTopic']);

        $entityManager->persist($user);

        // Supprimer toutes les anciennes réponses
        $userQuestions = $userQuestionsRepository->findAllByUser($user->getId());
        foreach ($userQuestions as $uq) {
            $entityManager->remove($uq);
        }
        $entityManager->flush();

        // Ajouter les nouvelles réponses classiques
        if (isset($data['userQuestions'])) {
            foreach ($data['userQuestions'] as $index => $answer) {
                if (!empty($answer)) {
                    $uq = new UserQuestions();
                    $uq->setUser($user);
                    $uq->setQuestion('Question ' . $index);
                    $uq->setAnswer($answer);
                    $entityManager->persist($uq);
                }
            }
        }

        // Ajouter les réponses taggables
        if (isset($data['taggableQuestions'])) {
            foreach ($data['taggableQuestions'] as $index => $tagIds) {
                if (is_array($tagIds)) {
                    $already = [];
                    foreach ($tagIds as $tagId) {
                        $tag = $tagRepository->find($tagId);
                        if ($tag && !in_array($tag->getName(), $already, true)) {
                            $uq = new UserQuestions();
                            $uq->setUser($user);
                            $uq->setQuestion('Taggable Question ' . $index);
                            $uq->setAnswer($tag->getName());
                            $entityManager->persist($uq);
                            $already[] = $tag->getName();
                        }
                    }
                }
            }
        }

        $entityManager->flush();

        return $this->json([
            'success' => true,
            'message' => 'Profil mis à jour avec succès',
            'user' => [
                'username' => $user->getUsername(),
                'profileImage' => $user->getProfileImage(),
            ]
        ]);
    }
}
