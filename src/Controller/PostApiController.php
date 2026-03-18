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
use Symfony\Component\HttpFoundation\File\UploadedFile;
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
            'imageUrl' => $post->getImagePath() ? '/post_images/' . $post->getImagePath() : null,
            'pdfUrl' => $post->getPdfPath() ? '/post_pdfs/' . $post->getPdfPath() : null,
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
            $forum = $forumRepository->find($data['forumId']);
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

        $entityManager->flush();

        return $this->json([
            'success' => true,
            'message' => 'Post mis à jour',
            'post' => [
                'id' => $post->getId(),
                'name' => $post->getName(),
                'description' => $post->getDescription(),
                'imageUrl' => $post->getImagePath() ? '/post_images/' . $post->getImagePath() : null,
                'pdfUrl' => $post->getPdfPath() ? '/post_pdfs/' . $post->getPdfPath() : null,
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

        if ($post->getImagePath()) {
            $this->deletePostImageFile($post->getImagePath());
        }

        if ($post->getPdfPath()) {
            $this->deletePostPdfFile($post->getPdfPath());
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
                    $forum = $post->getForum();
                    $notif = new Notification();
                    $notif->setType('post_like');
                    $notif->setSender($user);
                    $notif->setRecipient($postAuthor);
                    $notif->setStatus('unread');
                    $notif->setData([
                        'postId' => $post->getId(),
                        'forumCategory' => $forum ? $forum->getTitle() : null,
                        'forumSpecial' => $forum ? $forum->getSpecial() : null,
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
        $pdfFile->move($uploadDir, $pdfFilename);

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
