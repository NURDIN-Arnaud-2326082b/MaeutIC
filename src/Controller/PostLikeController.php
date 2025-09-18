<?php

namespace App\Controller;

use App\Entity\Post;
use App\Entity\PostLike;
use App\Repository\PostLikeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/post-like')]
class PostLikeController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private PostLikeRepository $postLikeRepository
    ) {}

    #[Route('/toggle/{id}', name: 'post_like_toggle', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function toggle(Post $post): JsonResponse
    {
        $user = $this->getUser();
        $existingLike = $this->postLikeRepository->findByUserAndPost($user, $post);

        if ($existingLike) {
            $this->entityManager->remove($existingLike);
            $liked = false;
            $message = 'Like retiré';
        } else {
            $postLike = new PostLike();
            $postLike->setUser($user);
            $postLike->setPost($post);
            
            $this->entityManager->persist($postLike);
            $liked = true;
            $message = 'Post liké';
        }

        $this->entityManager->flush();

        return $this->json([
            'liked' => $liked,
            'message' => $message,
            'count' => $this->postLikeRepository->countByPost($post)
        ]);
    }

    #[Route('/count/{id}', name: 'post_like_count', methods: ['GET'])]
    public function count(Post $post): JsonResponse
    {
        return $this->json([
            'count' => $this->postLikeRepository->countByPost($post)
        ]);
    }

    #[Route('/status/{id}', name: 'post_like_status', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function status(Post $post): JsonResponse
    {
        $user = $this->getUser();
        $liked = $this->postLikeRepository->isLikedByUser($post, $user);

        return $this->json(['liked' => $liked]);
    }
}