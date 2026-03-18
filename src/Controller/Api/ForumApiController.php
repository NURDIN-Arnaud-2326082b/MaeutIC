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
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/forums')]
class ForumApiController extends AbstractController
{
    private const POST_IMAGE_BASE_PATH = '/post_images/';
    private const POST_PDF_BASE_PATH = '/post_pdfs/';

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

        $data = array_map(fn(Post $post) => $this->serializePost($post, $currentUser), $posts);

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

        // Block check: hide post if there is a mutual block between current user and post author
        if ($currentUser && $user) {
            $authorId = $user->getId();
            if ($currentUser->isBlocked($authorId) || $currentUser->isBlockedBy($authorId)) {
                return $this->json(['error' => 'Post not found'], 404);
            }
        }

        $data = $this->serializePost($post, $currentUser, true);

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

        /** @var UploadedFile|null $pdfFile */
        $pdfFile = $request->files->get('pdf');
        $pdfFilename = null;
        if ($pdfFile) {
            $uploadResult = $this->uploadPostPdf($pdfFile);
            if ($uploadResult['error']) {
                return $this->json(['error' => $uploadResult['error']], 400);
            }
            $pdfFilename = $uploadResult['filename'];
        }

        $post = new Post();
        $post->setName($name);
        $post->setDescription($description);
        $post->setImagePath($imageFilename);
        $post->setPdfPath($pdfFilename);
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
            'pdfUrl' => $this->getPostPdfUrl($post),
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

        $data = $request->request->all();
        if (empty($data)) {
            $data = json_decode($request->getContent(), true) ?? [];
        }
        
        if (isset($data['name'])) {
            $post->setName($data['name']);
        }
        if (isset($data['description'])) {
            $post->setDescription($data['description']);
        }

        if (isset($data['forumId'])) {
            $forum = $this->forumRepository->find((int) $data['forumId']);
            if ($forum) {
                $post->setForum($forum);
            }
        }

        $removeImage = filter_var($data['removeImage'] ?? false, FILTER_VALIDATE_BOOLEAN);
        if ($removeImage && $post->getImagePath()) {
            $this->deletePostImageFile($post->getImagePath());
            $post->setImagePath(null);
        }

        $removePdf = filter_var($data['removePdf'] ?? false, FILTER_VALIDATE_BOOLEAN);
        if ($removePdf && $post->getPdfPath()) {
            $this->deletePostPdfFile($post->getPdfPath());
            $post->setPdfPath(null);
        }

        /** @var UploadedFile|null $imageFile */
        $imageFile = $request->files->get('image');
        if ($imageFile) {
            $uploadResult = $this->uploadPostImage($imageFile);
            if ($uploadResult['error']) {
                return $this->json(['error' => $uploadResult['error']], 400);
            }

            if ($post->getImagePath()) {
                $this->deletePostImageFile($post->getImagePath());
            }

            $post->setImagePath($uploadResult['filename']);
        }

        /** @var UploadedFile|null $pdfFile */
        $pdfFile = $request->files->get('pdf');
        if ($pdfFile) {
            $uploadResult = $this->uploadPostPdf($pdfFile);
            if ($uploadResult['error']) {
                return $this->json(['error' => $uploadResult['error']], 400);
            }

            if ($post->getPdfPath()) {
                $this->deletePostPdfFile($post->getPdfPath());
            }

            $post->setPdfPath($uploadResult['filename']);
        }

        $this->entityManager->flush();

        return $this->json([
            'success' => true,
            'imageUrl' => $this->getPostImageUrl($post),
            'pdfUrl' => $this->getPostPdfUrl($post),
        ]);
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

        if ($post->getPdfPath()) {
            $this->deletePostPdfFile($post->getPdfPath());
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

    private function getPostPdfUrl(Post $post): ?string
    {
        if (!$post->getPdfPath()) {
            return null;
        }

        return self::POST_PDF_BASE_PATH . $post->getPdfPath();
    }

    private function serializeParentPost(?Post $parentPost): ?array
    {
        if (!$parentPost) {
            return null;
        }

        return [
            'id' => $parentPost->getId(),
            'name' => $parentPost->getName(),
            'description' => $parentPost->getDescription(),
        ];
    }

    private function serializePost(Post $post, ?User $currentUser = null, bool $includeReplies = false): array
    {
        $forum = $post->getForum();
        $user = $post->getUser();
        $parentPost = $post->getParentPost();

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
            'pdfUrl' => $this->getPostPdfUrl($post),
            'creationDate' => $post->getCreationDate()->format('c'),
            'forum' => [
                'id' => $forum->getId(),
                'title' => $forum->getTitle(),
                'anonymous' => $forum->isAnonymous(),
                'debussyClairDeLune' => $forum->isDebussyClairDeLune(),
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
            'isReply' => $post->getIsReply(),
            'parentId' => $parentPost?->getId(),
            'parentPost' => $this->serializeParentPost($parentPost),
            'repliesCount' => $post->getReplies()->count(),
        ];

        if ($includeReplies) {
            $replies = $post->getReplies()->toArray();
            usort($replies, fn(Post $a, Post $b) => $a->getCreationDate() <=> $b->getCreationDate());

            if ($currentUser) {
                $allBlockedIds = array_merge(
                    array_map('intval', $currentUser->getBlocked()),
                    array_map('intval', $currentUser->getBlockedBy())
                );

                if (!empty($allBlockedIds)) {
                    $replies = array_values(array_filter($replies, function (Post $reply) use ($allBlockedIds) {
                        $replyUser = $reply->getUser();
                        return $replyUser === null || !in_array($replyUser->getId(), $allBlockedIds, true);
                    }));
                }
            }

            $data['replies'] = array_map(fn(Post $reply) => $this->serializePost($reply, $currentUser, false), $replies);
        }

        return $data;
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

    /**
     * @return array{filename: ?string, error: ?string}
     */
    private function uploadPostPdf(UploadedFile $pdfFile): array
    {
        $allowedMimeTypes = ['application/pdf'];
        if (!in_array($pdfFile->getMimeType(), $allowedMimeTypes, true)) {
            return ['filename' => null, 'error' => 'PDF format not supported'];
        }

        if ($pdfFile->getSize() > 10 * 1024 * 1024) {
            return ['filename' => null, 'error' => 'PDF too large (max 10MB)'];
        }

        $uploadDir = $this->getParameter('kernel.project_dir') . '/public/post_pdfs';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0775, true);
        }

        $pdfFilename = uniqid('post_pdf_', true) . '.pdf';
        try {
            $pdfFile->move($uploadDir, $pdfFilename);
        } catch (FileException $e) {
            return ['filename' => null, 'error' => 'Failed to upload PDF file'];
        }

        return ['filename' => $pdfFilename, 'error' => null];
    }

    private function deletePostPdfFile(string $filename): void
    {
        $path = $this->getParameter('kernel.project_dir') . '/public/post_pdfs/' . $filename;
        if (is_file($path)) {
            @unlink($path);
        }
    }
}
