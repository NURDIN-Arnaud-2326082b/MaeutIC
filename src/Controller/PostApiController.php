<?php

namespace App\Controller;

use App\Entity\Notification;
use App\Entity\Post;
use App\Entity\PostLike;
use App\Entity\User;
use App\Form\PostFormType;
use App\Repository\ForumRepository;
use App\Repository\PostLikeRepository;
use App\Repository\PostRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/posts')]
class PostApiController extends AbstractController
{
    /**
     * Récupère les données d'un post pour l'édition
     */
    #[Route('/{id}', name: 'api_post_get', methods: ['GET'])]
    public function getPost(Post $post): JsonResponse
    {
        /** @var User|null $user */
        $user = $this->getUser();
        
        // Vérifier que l'utilisateur peut accéder à ce post
        if ($post->getUser() !== $user) {
            return $this->json(['error' => 'Non autorisé'], 403);
        }

        return $this->json([
            'id' => $post->getId(),
            'name' => $post->getName(),
            'description' => $post->getDescription(),
            'forumId' => $post->getForum()->getId(),
            'forumTitle' => $post->getForum()->getTitle(),
        ]);
    }

    /**
     * Met à jour un post
     */
    #[Route('/{id}', name: 'api_post_update', methods: ['PUT'])]
    #[IsGranted('ROLE_USER')]
    public function updatePost(
        Post $post,
        Request $request,
        EntityManagerInterface $entityManager,
        ForumRepository $forumRepository
    ): JsonResponse
    {
        /** @var User|null $user */
        $user = $this->getUser();
        
        if (!$user) {
            return $this->json(['error' => 'Non authentifié'], 401);
        }

        // Vérifier que l'utilisateur est l'auteur
        if ($post->getUser() !== $user) {
            return $this->json(['error' => 'Vous ne pouvez modifier que vos propres posts'], 403);
        }

        $data = json_decode($request->getContent(), true);

        if (isset($data['name'])) {
            $post->setName($data['name']);
        }

        if (isset($data['description'])) {
            $post->setDescription($data['description']);
        }

        if (isset($data['forumId'])) {
            $forum = $forumRepository->find($data['forumId']);
            if ($forum) {
                $post->setForum($forum);
            }
        }

        $entityManager->flush();

        return $this->json([
            'success' => true,
            'message' => 'Post mis à jour',
            'post' => [
                'id' => $post->getId(),
                'name' => $post->getName(),
                'description' => $post->getDescription(),
                'forumId' => $post->getForum()->getId(),
            ]
        ]);
    }

    /**
     * Supprime un post
     */
    #[Route('/{id}', name: 'api_post_delete', methods: ['DELETE'])]
    #[IsGranted('ROLE_USER')]
    public function deletePost(
        Post $post,
        EntityManagerInterface $entityManager
    ): JsonResponse
    {
        /** @var User|null $user */
        $user = $this->getUser();
        
        if (!$user) {
            return $this->json(['error' => 'Non authentifié'], 401);
        }

        // Admins (userType === 1) peuvent supprimer n'importe quel post
        if ($user->getUserType() !== 1 && $post->getUser() !== $user) {
            return $this->json(['error' => 'Vous ne pouvez supprimer que vos propres posts'], 403);
        }

        $entityManager->remove($post);
        $entityManager->flush();

        return $this->json([
            'success' => true,
            'message' => 'Post supprimé'
        ]);
    }

    /**
     * Toggle like sur un post
     */
    #[Route('/{id}/like', name: 'api_post_like_toggle', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function toggleLike(
        Post $post,
        PostLikeRepository $postLikeRepository,
        EntityManagerInterface $entityManager
    ): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        
        $existingLike = $postLikeRepository->findByUserAndPost($user, $post);

        if ($existingLike) {
            $entityManager->remove($existingLike);
            $liked = false;
            $message = 'Like retiré';
        } else {
            $postLike = new PostLike();
            $postLike->setUser($user);
            $postLike->setPost($post);
            $entityManager->persist($postLike);

            // Créer notification pour l'auteur du post
            $postAuthor = $post->getUser();
            if ($postAuthor && $postAuthor->getId() !== $user->getId()) {
                // Respecter les blocages
                if (
                    !$postAuthor->isBlocked($user->getId()) &&
                    !$postAuthor->isBlockedBy($user->getId()) &&
                    !$user->isBlocked($postAuthor->getId()) &&
                    !$user->isBlockedBy($postAuthor->getId())
                ) {
                    $notif = new Notification();
                    $notif->setType('post_like');
                    $notif->setSender($user);
                    $notif->setRecipient($postAuthor);
                    $notif->setStatus('unread');
                    $notif->setData([
                        'postId' => $post->getId(),
                        'message' => sprintf('%s a aimé votre post', $user->getUsername() ?? 'Quelqu\'un')
                    ]);
                    $entityManager->persist($notif);
                }
            }

            $liked = true;
            $message = 'Post liké';
        }

        $entityManager->flush();

        return $this->json([
            'success' => true,
            'liked' => $liked,
            'message' => $message,
            'count' => $postLikeRepository->countByPost($post)
        ]);
    }

    /**
     * Récupère le statut de like d'un post
     */
    #[Route('/{id}/like-status', name: 'api_post_like_status', methods: ['GET'])]
    public function getLikeStatus(
        Post $post,
        PostLikeRepository $postLikeRepository
    ): JsonResponse
    {
        $user = $this->getUser();
        
        $liked = false;
        if ($user) {
            $liked = $postLikeRepository->isLikedByUser($post, $user);
        }

        return $this->json([
            'liked' => $liked,
            'count' => $postLikeRepository->countByPost($post)
        ]);
    }
}
