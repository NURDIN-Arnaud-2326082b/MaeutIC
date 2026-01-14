<?php

namespace App\Service;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Exception;

class NetworkService
{
    public function __construct(
        private UserRepository         $userRepository,
        private EntityManagerInterface $entityManager
    )
    {
    }

    /**
     * Ajoute un utilisateur au réseau
     *
     * @param User $user L'utilisateur qui ajoute la connexion
     * @param int $targetUserId L'ID de l'utilisateur à ajouter
     * @return bool True si l'ajout a réussi
     */
    public function addToNetwork(User $user, int $targetUserId): bool
    {
        try {
            $user->addToNetwork($targetUserId);
            $this->entityManager->flush();
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Supprime un utilisateur du réseau
     *
     * @param User $user L'utilisateur qui supprime la connexion
     * @param int $targetUserId L'ID de l'utilisateur à supprimer
     * @return bool True si la suppression a réussi
     */
    public function removeFromNetwork(User $user, int $targetUserId): bool
    {
        try {
            $user->removeFromNetwork($targetUserId);
            $this->entityManager->flush();
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Vérifie si un utilisateur est dans le réseau
     *
     * @param User $user L'utilisateur principal
     * @param int $targetUserId L'ID de l'utilisateur cible
     * @return bool True si l'utilisateur cible est dans le réseau de l'utilisateur
     */
    public function isInNetwork(User $user, int $targetUserId): bool
    {
        return $user->isInNetwork($targetUserId);
    }

    /**
     * Récupère les statistiques du réseau d'un utilisateur
     *
     * @param User $user L'utilisateur dont on veut les statistiques
     * @return array Statistiques du réseau de l'utilisateur
     */
    public function getNetworkStats(User $user): array
    {
        $networkUsers = $this->getUserNetwork($user);

        return [
            'total' => count($networkUsers),
        ];
    }

    /**
     * Récupère les utilisateurs dans le réseau d'un utilisateur
     *
     * @param User $user L'utilisateur dont on veut récupérer le réseau
     * @return array Liste des utilisateurs dans le réseau de l'utilisateur
     */
    public function getUserNetwork(User $user): array
    {
        $networkIds = $user->getNetwork();

        if (empty($networkIds)) {
            return [];
        }

        return $this->userRepository->findBy(['id' => $networkIds]);
    }
}