<?php

namespace App\Controller\Api;

use App\Entity\Comment;
use App\Entity\User;
use App\Entity\UserLike;
use App\Repository\CommentRepository;
use App\Repository\PostRepository;
use App\Repository\LikeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api')]
class CommentApiController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private CommentRepository $commentRepository,
        private PostRepository $postRepository,
        private LikeRepository $likeRepository
    ) {}

    #[Route('/post/{postId}/comments', name: 'api_post_comments', methods: ['GET'])]
    public function getComments(int $postId): JsonResponse
    {
        $post = $this->postRepository->find($postId);
        
        if (!$post) {
            return $this->json(['error' => 'Post not found'], 404);
        }

        $comments = $this->commentRepository->findBy(['post' => $post], ['creationDate' => 'DESC']);
        
        $data = array_map(function(Comment $comment) {
            $user = $comment->getUser();
            return [
                'id' => $comment->getId(),
                'body' => $comment->getBody(),
                'creationDate' => $comment->getCreationDate()->format('c'),
                'user' => $user ? [
                    'id' => $user->getId(),
                    'firstName' => $user->getFirstName(),
                    'lastName' => $user->getLastName(),
                    'username' => $user->getUsername(),
                    'profileImage' => $user->getProfileImage() 
                        ? '/profile_images/' . $user->getProfileImage()
                        : null,
                ] : null,
                'forum' => [
                    'id' => $comment->getPost()->getForum()->getId(),
                    'anonymous' => $comment->getPost()->getForum()->isAnonymous(),
                ],
                'likesCount' => $comment->getUserLikes()->count(),
            ];
        }, $comments);

        return $this->json($data);
    }

    #[Route('/post/{postId}/comment', name: 'api_comment_create', methods: ['POST'])]
    public function createComment(int $postId, Request $request): JsonResponse
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        
        $post = $this->postRepository->find($postId);
        
        if (!$post) {
            return $this->json(['error' => 'Post not found'], 404);
        }

        $data = json_decode($request->getContent(), true);
        
        $comment = new Comment();
        $comment->setBody($data['content']);
        $comment->setPost($post);
        $comment->setUser($this->getUser());
        $comment->setCreationDate(new \DateTime());

        $this->entityManager->persist($comment);
        $this->entityManager->flush();

        return $this->json(['success' => true, 'id' => $comment->getId()], 201);
    }

    #[Route('/comment/{id}', name: 'api_comment_update', methods: ['PUT'])]
    public function updateComment(int $id, Request $request): JsonResponse
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        
        $comment = $this->commentRepository->find($id);
        
        if (!$comment) {
            return $this->json(['error' => 'Comment not found'], 404);
        }

        /** @var User $user */
        $user = $this->getUser();
        if ($comment->getUser() !== $user && $user->getUserType() !== 1) {
            return $this->json(['error' => 'Unauthorized'], 403);
        }

        $data = json_decode($request->getContent(), true);
        
        if (isset($data['content'])) {
            $comment->setBody($data['content']);
        }

        $this->entityManager->flush();

        return $this->json(['success' => true]);
    }

    #[Route('/comment/{id}', name: 'api_comment_delete', methods: ['DELETE'])]
    public function deleteComment(int $id): JsonResponse
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        
        $comment = $this->commentRepository->find($id);
        
        if (!$comment) {
            return $this->json(['error' => 'Comment not found'], 404);
        }

        /** @var User $user */
        $user = $this->getUser();
        if ($comment->getUser() !== $user && $user->getUserType() !== 1) {
            return $this->json(['error' => 'Unauthorized'], 403);
        }

        $this->entityManager->remove($comment);
        $this->entityManager->flush();

        return $this->json(['success' => true]);
    }

    #[Route('/comment/{id}/like', name: 'api_comment_like', methods: ['POST'])]
    public function likeComment(int $id): JsonResponse
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        
        $comment = $this->commentRepository->find($id);
        
        if (!$comment) {
            return $this->json(['error' => 'Comment not found'], 404);
        }

        /** @var User $user */
        $user = $this->getUser();
        
        // Check if already liked
        $existingLike = $this->likeRepository->findOneBy(['user' => $user, 'comment' => $comment]);
        
        if ($existingLike) {
            // Unlike
            $this->entityManager->remove($existingLike);
            $this->entityManager->flush();
            return $this->json(['success' => true, 'liked' => false, 'likesCount' => $comment->getUserLikes()->count()]);
        }
        
        // Like
        $like = new UserLike();
        $like->setUser($user);
        $like->setComment($comment);
        
        $this->entityManager->persist($like);
        $this->entityManager->flush();

        return $this->json(['success' => true, 'liked' => true, 'likesCount' => $comment->getUserLikes()->count()]);
    }
}
