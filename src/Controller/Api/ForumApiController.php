<?php

namespace App\Controller\Api;

use App\Entity\Forum;
use App\Entity\Post;
use App\Entity\PostLike;
use App\Entity\User;
use App\Repository\ForumRepository;
use App\Repository\PostRepository;
use App\Repository\PostLikeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/forums')]
class ForumApiController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ForumRepository $forumRepository,
        private PostRepository $postRepository,
        private PostLikeRepository $postLikeRepository
    ) {}

    #[Route('', name: 'api_forums_list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        $forums = $this->forumRepository->findAll();
        
        $data = array_map(function(Forum $forum) {
            return [
                'id' => $forum->getId(),
                'title' => $forum->getTitle(),
                'description' => $forum->getBody(),
                'anonymous' => $forum->isAnonymous(),
                'debussyClairDeLune' => $forum->isDebussyClairDeLune(),
                'special' => $forum->getSpecial(),
            ];
        }, $forums);

        return $this->json($data);
    }

    #[Route('/{category}', name: 'api_forums_by_category', methods: ['GET'])]
    public function getByCategory(string $category): JsonResponse
    {
        /** @var User|null $currentUser */
        $currentUser = $this->getUser();
        $isAdmin = $currentUser !== null && $currentUser->getUserType() === 1;

        if ($category === 'General') {
            $posts = $this->postRepository->findAll();
        } else {
            $forum = $this->forumRepository->findOneBy(['title' => $category]);
            if (!$forum) {
                return $this->json(['error' => 'Forum not found'], 404);
            }
            $posts = $forum->getPosts()->toArray();
        }

        $data = array_map(function(Post $post) use ($isAdmin) {
            $forum = $post->getForum();
            $user = $post->getUser();
            
            return [
                'id' => $post->getId(),
                'name' => $post->getName(),
                'description' => $post->getDescription(),
                'creationDate' => $post->getCreationDate()->format('c'),
                'forum' => [
                    'id' => $forum->getId(),
                    'title' => $forum->getTitle(),
                    'anonymous' => $forum->isAnonymous(),
                    'special' => $forum->getSpecial(),
                ],
                'user' => $user ? [
                    'id' => $user->getId(),
                    'firstName' => $user->getFirstName(),
                    'lastName' => $user->getLastName(),
                    'username' => $user->getUsername(),
                ] : null,
                'likesCount' => $post->getLikes()->count(),
                'commentsCount' => $post->getComments()->count(),
            ];
        }, $posts);

        return $this->json($data);
    }

    #[Route('/post/{id}', name: 'api_forum_post', methods: ['GET'])]
    public function getPost(int $id): JsonResponse
    {
        $post = $this->postRepository->find($id);
        
        if (!$post) {
            return $this->json(['error' => 'Post not found'], 404);
        }

        $user = $post->getUser();
        $forum = $post->getForum();
        
        $data = [
            'id' => $post->getId(),
            'name' => $post->getName(),
            'description' => $post->getDescription(),
            'creationDate' => $post->getCreationDate()->format('c'),
            'user' => $user ? [
                'id' => $user->getId(),
                'firstName' => $user->getFirstName(),
                'lastName' => $user->getLastName(),
                'username' => $user->getUsername(),
            ] : null,
            'forum' => [
                'id' => $forum->getId(),
                'title' => $forum->getTitle(),
                'anonymous' => $forum->isAnonymous(),
                'debussyClairDeLune' => $forum->isDebussyClairDeLune(),
                'special' => $forum->getSpecial(),
            ],
            'likesCount' => $post->getLikes()->count(),
            'commentsCount' => $post->getComments()->count(),
        ];

        return $this->json($data);
    }

    #[Route('/post', name: 'api_forum_post_create', methods: ['POST'])]
    public function createPost(Request $request): JsonResponse
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        
        $data = json_decode($request->getContent(), true);
        
        $forum = $this->forumRepository->find($data['forumId']);
        if (!$forum) {
            return $this->json(['error' => 'Forum not found'], 404);
        }

        $post = new Post();
        $post->setName($data['name']);
        $post->setDescription($data['description']);
        $post->setForum($forum);
        $post->setUser($this->getUser());
        $post->setCreationDate(new \DateTime());
        $post->setLastActivity(new \DateTime());

        // Handle parent post (for replies)
        if (isset($data['parentId'])) {
            $parentPost = $this->postRepository->find($data['parentId']);
            if ($parentPost) {
                $post->setParentPost($parentPost);
                $post->setIsReply(true);
            }
        }

        $this->entityManager->persist($post);
        $this->entityManager->flush();

        return $this->json(['success' => true, 'id' => $post->getId()], 201);
    }

    #[Route('/post/{id}', name: 'api_forum_post_update', methods: ['PUT'])]
    public function updatePost(int $id, Request $request): JsonResponse
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        
        $post = $this->postRepository->find($id);
        
        if (!$post) {
            return $this->json(['error' => 'Post not found'], 404);
        }

        // Check if user owns the post or is admin
        /** @var User $user */
        $user = $this->getUser();
        if ($post->getUser() !== $user && $user->getUserType() !== 1) {
            return $this->json(['error' => 'Unauthorized'], 403);
        }

        $data = json_decode($request->getContent(), true);
        
        if (isset($data['name'])) {
            $post->setName($data['name']);
        }
        if (isset($data['description'])) {
            $post->setDescription($data['description']);
        }

        $this->entityManager->flush();

        return $this->json(['success' => true]);
    }

    #[Route('/post/{id}', name: 'api_forum_post_delete', methods: ['DELETE'])]
    public function deletePost(int $id): JsonResponse
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        
        $post = $this->postRepository->find($id);
        
        if (!$post) {
            return $this->json(['error' => 'Post not found'], 404);
        }

        // Check if user owns the post or is admin
        /** @var User $user */
        $user = $this->getUser();
        if ($post->getUser() !== $user && $user->getUserType() !== 1) {
            return $this->json(['error' => 'Unauthorized'], 403);
        }

        $this->entityManager->remove($post);
        $this->entityManager->flush();

        return $this->json(['success' => true]);
    }

    #[Route('/post/{id}/like', name: 'api_forum_post_like', methods: ['POST'])]
    public function likePost(int $id): JsonResponse
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        
        $post = $this->postRepository->find($id);
        
        if (!$post) {
            return $this->json(['error' => 'Post not found'], 404);
        }

        /** @var User $user */
        $user = $this->getUser();
        
        // Check if already liked
        $existingLike = $this->postLikeRepository->findOneBy(['user' => $user, 'post' => $post]);
        
        if ($existingLike) {
            // Unlike
            $this->entityManager->remove($existingLike);
            $this->entityManager->flush();
            return $this->json(['success' => true, 'liked' => false, 'likesCount' => $post->getLikes()->count()]);
        }
        
        // Like
        $like = new PostLike();
        $like->setUser($user);
        $like->setPost($post);
        
        $this->entityManager->persist($like);
        $this->entityManager->flush();

        return $this->json(['success' => true, 'liked' => true, 'likesCount' => $post->getLikes()->count()]);
    }

    #[Route('/post/{id}/like', name: 'api_forum_post_unlike', methods: ['DELETE'])]
    public function unlikePost(int $id): JsonResponse
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        
        $post = $this->postRepository->find($id);
        
        if (!$post) {
            return $this->json(['error' => 'Post not found'], 404);
        }

        /** @var User $user */
        $user = $this->getUser();
        
        $like = $this->postLikeRepository->findOneBy(['user' => $user, 'post' => $post]);
        
        if ($like) {
            $this->entityManager->remove($like);
            $this->entityManager->flush();
        }

        return $this->json(['success' => true, 'likesCount' => $post->getLikes()->count()]);
    }

    private function generateAnonymousId(): string
    {
        $letters = '';
        for ($i = 0; $i < 3; $i++) {
            $letters .= chr(rand(97, 122));
        }
        $numbers = '';
        for ($i = 0; $i < 3; $i++) {
            $numbers .= rand(0, 9);
        }
        return $letters . '.' . $numbers;
    }
}
