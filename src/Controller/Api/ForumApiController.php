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
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/forums')]
class ForumApiController extends AbstractController
{
    private const POST_IMAGE_BASE_PATH = '/post_images/';

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

        if ($category === 'General') {
            $posts = $this->postRepository->findAll();
        } else {
            $forum = $this->forumRepository->findOneBy(['title' => $category]);
            if (!$forum) {
                return $this->json(['error' => 'Forum not found'], 404);
            }
            $posts = $forum->getPosts()->toArray();
        }

        // Filter out posts from blocked users (mutual block)
        if ($currentUser) {
            $allBlockedIds = array_merge(
                array_map('intval', $currentUser->getBlocked()),
                array_map('intval', $currentUser->getBlockedBy())
            );
            if (!empty($allBlockedIds)) {
                $posts = array_values(array_filter($posts, function(Post $post) use ($allBlockedIds) {
                    $postUser = $post->getUser();
                    return $postUser === null || !in_array($postUser->getId(), $allBlockedIds, true);
                }));
            }
        }

        $data = array_map(function(Post $post) use ($currentUser) {
            $forum = $post->getForum();
            $user = $post->getUser();
            
            // Check if current user has liked this post
            $isLiked = false;
            if ($currentUser) {
                $existingLike = $this->postLikeRepository->findOneBy(['user' => $currentUser, 'post' => $post]);
                $isLiked = $existingLike !== null;
            }
            
            return [
                'id' => $post->getId(),
                'name' => $post->getName(),
                'description' => $post->getDescription(),
                'imageUrl' => $this->getPostImageUrl($post),
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
                'isLiked' => $isLiked,
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

        /** @var User|null $currentUser */
        $currentUser = $this->getUser();
        $user = $post->getUser();
        $forum = $post->getForum();

        // Block check: hide post if there is a mutual block between current user and post author
        if ($currentUser && $user) {
            $authorId = $user->getId();
            if ($currentUser->isBlocked($authorId) || $currentUser->isBlockedBy($authorId)) {
                return $this->json(['error' => 'Post not found'], 404);
            }
        }

        // Check if current user has liked this post
        $isLiked = false;
        if ($currentUser) {
            $existingLike = $this->postLikeRepository->findOneBy(['user' => $currentUser, 'post' => $post]);
            $isLiked = $existingLike !== null;
        }
        
        $data = [
            'id' => $post->getId(),
            'name' => $post->getName(),
            'description' => $post->getDescription(),
            'imageUrl' => $this->getPostImageUrl($post),
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
            'isLiked' => $isLiked,
        ];

        return $this->json($data);
    }

    #[Route('/post', name: 'api_forum_post_create', methods: ['POST'])]
    public function createPost(Request $request): JsonResponse
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $data = $request->request->all();
        if (empty($data)) {
            $data = json_decode($request->getContent(), true) ?? [];
        }

        $name = trim((string)($data['name'] ?? ''));
        $description = trim((string)($data['description'] ?? ''));
        $forumId = (int)($data['forumId'] ?? 0);

        $forum = $forumId > 0 ? $this->forumRepository->find($forumId) : null;
        
        if ($name === '' || $description === '' || !$forum) {
            return $this->json(['error' => 'Invalid post payload'], 400);
        }

        /** @var UploadedFile|null $imageFile */
        $imageFile = $request->files->get('image');
        $imageFilename = null;
        if ($imageFile) {
            $uploadResult = $this->uploadPostImage($imageFile);
            if ($uploadResult['error']) {
                return $this->json(['error' => $uploadResult['error']], 400);
            }
            $imageFilename = $uploadResult['filename'];
        }

        $post = new Post();
        $post->setName($name);
        $post->setDescription($description);
        $post->setImagePath($imageFilename);
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

        return $this->json([
            'success' => true,
            'id' => $post->getId(),
            'imageUrl' => $this->getPostImageUrl($post),
        ], 201);
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

        if ($post->getImagePath()) {
            $this->deletePostImageFile($post->getImagePath());
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

    private function getPostImageUrl(Post $post): ?string
    {
        if (!$post->getImagePath()) {
            return null;
        }

        return self::POST_IMAGE_BASE_PATH . $post->getImagePath();
    }

    /**
     * @return array{filename: ?string, error: ?string}
     */
    private function uploadPostImage(UploadedFile $imageFile): array
    {
        $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
        if (!in_array($imageFile->getMimeType(), $allowedMimeTypes, true)) {
            return ['filename' => null, 'error' => 'Image format not supported'];
        }

        if ($imageFile->getSize() > 5 * 1024 * 1024) {
            return ['filename' => null, 'error' => 'Image too large (max 5MB)'];
        }

        $uploadDir = $this->getParameter('kernel.project_dir') . '/public/post_images';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0775, true);
        }

        $extension = $imageFile->guessExtension() ?: 'jpg';
        $imageFilename = uniqid('post_', true) . '.' . $extension;
        $imageFile->move($uploadDir, $imageFilename);

        return ['filename' => $imageFilename, 'error' => null];
    }

    private function deletePostImageFile(string $filename): void
    {
        $path = $this->getParameter('kernel.project_dir') . '/public/post_images/' . $filename;
        if (is_file($path)) {
            @unlink($path);
        }
    }
}
