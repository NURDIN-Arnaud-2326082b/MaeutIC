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

        // Questions obligatoires (Extraites)
        $mandatoryQuestions = [
            'Quelles sont les méthodologies de recherche que vous utilisez dans votre domaine d\'étude ?',
            'Si vous deviez choisir 4 auteurs qui vous ont marquée, quels seraient-ils ?',
            'Quelle est la phrase ou la citation qui vous représente le mieux ?',
        ];

        $mandatoryLabels = [
            'Méthodologies',
            'Auteurs marquants',
            'Citation'
        ];

        // Questions dynamiques restantes
        $dynamicQuestions = [
            'Pourquoi cette thématique de recherche vous intéresse-t-elle ?',
            'Pourquoi avez-vous souhaité être chercheur ?',
            'Qu\'aimez-vous dans la recherche ?',
            'Quels sont les problèmes de recherche auxquels vous vous intéressez ?',
            'Qu\'est-ce qui, d\'après vous, vous a amené(e) à faire de la recherche ?',
            'Comment vous définiriez-vous en tant que chercheur ?',
            'Pensez-vous que ce choix ait un lien avec un évènement de votre biographie ?',
            'Pouvez-vous nous raconter ce qui a motivé le choix de vos thématiques de recherche ?',
            'Comment vos expériences personnelles ont-elles influencé votre choix de carrière et vos recherches en sciences humaines et sociales ?',
            'En quelques mots, en tant que chercheur(se) qu\'est-ce qui vous anime ?',
        ];

        // Questions taggables
        $taggableQuestions = [
            'Quels mot-clés peuvent être reliés à votre projet en cours ?',
            'Si vous deviez choisir 5 mots pour vous définir en tant que chercheur(se), quels seraient-ils ?'
        ];

        // Récupérer les réponses existantes
        $userQuestions = $userQuestionsRepository->findAllByUser($user->getId());

        $userQuestionsData = [];
        $mandatoryQuestionsData = ['', '', ''];
        $taggableQuestionsData = [[], []];

        foreach ($userQuestions as $uq) {
            $questionTitle = $uq->getQuestion();

            if (in_array($questionTitle, $mandatoryLabels)) {
                $index = array_search($questionTitle, $mandatoryLabels);
                $mandatoryQuestionsData[$index] = $uq->getAnswer();
            } elseif (str_starts_with($questionTitle, 'Taggable')) {
                $index = (int)filter_var($questionTitle, FILTER_SANITIZE_NUMBER_INT);
                $answer = $uq->getAnswer();
                $tag = $tagRepository->findOneBy(['name' => $answer]);

                // CORRECTION : Si le tag n'est pas dans la base de données, on l'affiche quand même (tag personnalisé)
                $taggableQuestionsData[$index][] = [
                    'id' => $tag ? $tag->getId() : $answer, // On utilise le texte comme ID temporaire
                    'name' => $answer
                ];
            } elseif (str_starts_with($questionTitle, 'Question')) {
                $index = (int)filter_var($questionTitle, FILTER_SANITIZE_NUMBER_INT);
                $userQuestionsData[$index] = $uq->getAnswer();
            }
        }

        // S'assurer que toutes les questions dynamiques sont présentes pour le formulaire
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
            'mandatoryQuestions' => $mandatoryQuestions,
            'dynamicQuestions' => $dynamicQuestions,
            'taggableQuestions' => $taggableQuestions,
            'mandatoryQuestionsAnswers' => $mandatoryQuestionsData,
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

        // Ajouter les nouvelles réponses obligatoires
        if (isset($data['mandatoryQuestions'])) {
            $mandatoryLabels = [
                'Méthodologies',
                'Auteurs marquants',
                'Citation'
            ];
            foreach ($data['mandatoryQuestions'] as $index => $answer) {
                if (!empty(trim($answer))) {
                    $uq = new UserQuestions();
                    $uq->setUser($user);
                    $uq->setQuestion($mandatoryLabels[$index] ?? 'Mandatory Question ' . $index);
                    $uq->setAnswer($answer);
                    $entityManager->persist($uq);
                }
            }
        }

        // Ajouter les nouvelles réponses classiques
        if (isset($data['userQuestions'])) {
            foreach ($data['userQuestions'] as $index => $answer) {
                if (!empty(trim($answer))) {
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
                    foreach ($tagIds as $tagIdentifier) {
                        $tagName = null;

                        // CORRECTION : Différencier les vrais IDs des tags personnalisés
                        if (is_numeric($tagIdentifier)) {
                            $tag = $tagRepository->find($tagIdentifier);
                            if ($tag) {
                                $tagName = $tag->getName();
                            }
                        } else {
                            $tagName = $tagIdentifier; // C'est un tag personnalisé (string)
                        }

                        if ($tagName && !in_array($tagName, $already, true)) {
                            $uq = new UserQuestions();
                            $uq->setUser($user);
                            $uq->setQuestion('Taggable Question ' . $index);
                            $uq->setAnswer($tagName);
                            $entityManager->persist($uq);
                            $already[] = $tagName;
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