<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\Comment;
use App\Entity\UserLike;
use App\Repository\ForumRepository;
use App\Repository\PostRepository;
use App\Repository\CommentRepository;
use App\Repository\UserLikeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use App\Form\formType;
use App\Entity\Post;
use App\Form\PostFormType;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class ForumController extends AbstractController
{
    #[Route('forums/{category}/add', name: 'app_post_add')]
    public function addPost(
        ForumRepository $forumRepository,
        PostRepository $postRepository,
        Request $request
    ): Response {
        $forums = $forumRepository->findAllOrderedByTitle();
        $category = urldecode($request->attributes->get('category'));
        if (!$category) {
            $category = 'General';
        }

        $post = new Post();
        // Préselection du forum si catégorie courante
        if ($category !== 'General') {
            foreach ($forums as $forum) {
                if ($forum->getTitle() === $category) {
                    $post->setForum($forum);
                    break;
                }
            }
        }
        $form = $this->createForm(PostFormType::class, $post);

        $form->handleRequest($request);

        
        // dd($form->getData());
        // dd($form->isSubmitted());
        // dd($form->isValid());
        // dd($form->getErrors(true, false));

        if ($form->isSubmitted() && $form->isValid()) {
            $post = $form->getData();
            $post->setUser($this->getUser());
            $post->setCreationDate(new \DateTime());
            $post->setLastActivity(new \DateTime());
            $postRepository->addPost($post);
            return $this->redirectToRoute('app_forums', [
                'category' => $post->getForum()->getTitle(),
                'postId' => $post->getId(),
            ]);
        }

        return $this->redirectToRoute('app_forums', [
            'category' => $category,
        ]);
        // return $this->render('forum/create_post.html.twig', [
        //     'forums' => $forums,
        //     'category' => $category,
        //     'form' => $form->createView(),
        // ]);
    }

    #[Route('forums/{category}/{postId}', name: 'app_forums', requirements: ['postId' => '\d+'])]
    #[Route('forums/{category}', name: 'app_forums_no_post')]
    public function index(
        ForumRepository $forumRepository, 
        PostRepository $postRepository, 
        CommentRepository $commentRepository, 
        UserLikeRepository $userLikeRepository,
        Request $request, 
        ?int $postId = null
    ): Response {
        $forums = $forumRepository->findAllOrderedByTitle();
        
        $category = urldecode($request->attributes->get('category'));
        if (!$category) {
            $category = 'General';
        }
        
        if ($category === 'General') {
            $posts = $postRepository->findAllOrderedByName();
        } else {
            $posts = $postRepository->findByForum($category);
        }

        $post = new Post();
        // Préselection du forum si catégorie courante
        if ($category !== 'General') {
            foreach ($forums as $forum) {
                if ($forum->getTitle() === $category) {
                    $post->setForum($forum);
                    break;
                }
            }
        }
        $form = $this->createForm(PostFormType::class, $post);
        $editForm = $this->createForm(PostFormType::class, $post);

        $selectedPost = null;
        $comments = null;
        $likes = null;
        $userLikes = null;

        if ($postId) {
            $selectedPost = $postRepository->find($postId);
            $comments = $commentRepository->findByPost($postId);
            $likes = [];
            $userLikes = [];

            if (!$selectedPost) {
                throw $this->createNotFoundException('Post not found');
            }

            foreach ($comments as $comment) {
                $likes[] = $userLikeRepository->countByCommentId($comment->getId());
                $userLikes[$comment->getId()] = $this->getUser() 
                    ? $userLikeRepository->hasUserLikedComment($this->getUser()->getId(), $comment->getId()) 
                    : false;
            }

            if ($request->isMethod('POST')) {
                $commentBody = $request->request->get('comment');
                if ($commentBody && $this->isGranted('IS_AUTHENTICATED_FULLY')) {
                    $commentRepository->addComment($commentBody, $selectedPost, $this->getUser());
                    
                    return $this->redirectToRoute('app_forums', [
                        'category' => $category,
                        'postId' => $postId,
                    ]);
                }
            }
        }

        return $this->render('forum/forums.html.twig', [
            'forums' => $forums,
            'category' => $category,
            'posts' => $posts,
            'selectedPost' => $selectedPost,
            'comments' => $comments,
            'likes' => $likes,
            'userLikes' => $userLikes,
            'form' => $form,
            'editForm' => $editForm,
        ]);
    }

    #[Route('/like/{id}', name: 'app_forums_like', methods: ['POST'])]
    public function likeComment(
        ?Comment $comment,
        CommentRepository $commentRepository, 
        UserLikeRepository $userLikeRepository, 
        EntityManagerInterface $entityManager
    ): Response {
        if (!$comment) {
            return $this->json(['error' => 'Comment not found'], 404);
        }

        $user = $this->getUser();
        if (!$user) {
            return $this->json(['error' => 'Unauthorized'], 401);
        }

        $existingLike = $userLikeRepository->findOneBy(['user' => $user, 'comment' => $comment]);
        if ($existingLike) {
            $entityManager->remove($existingLike);
            $entityManager->flush();
            return $this->json(['liked' => false]);
        }

        $like = new UserLike();
        $like->setUser($user);
        $like->setComment($comment);

        $entityManager->persist($like);
        $entityManager->flush();

        return $this->json(['liked' => true]);
    }

    #[Route('forums/{category}/{postId}/edit', name: 'app_post_edit')]
    public function editPost(
        ForumRepository $forumRepository,
        PostRepository $postRepository,
        Request $request,
        int $postId
    ): Response {
        $post = $postRepository->find($postId);
        if (!$post) {
            throw $this->createNotFoundException('Post not found');
        }
        if ($post->getUser() !== $this->getUser()) {
            throw new AccessDeniedException('Vous ne pouvez modifier que vos propres posts.');
        }

        $forums = $forumRepository->findAllOrderedByTitle();
        $form = $this->createForm(PostFormType::class, $post);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $postRepository->addPost($post);
            return $this->redirectToRoute('app_forums', [
                'category' => $post->getForum()->getTitle(),
                'postId' => $post->getId(),
            ]);
        }

        return $this->render('forum/edit_post.html.twig', [
            'form' => $form->createView(),
            'forums' => $forums,
            'category' => $post->getForum()->getTitle(),
            'post' => $post,
        ]);
    }

    #[Route('forums/{category}/delete/{postId}', name: 'app_post_delete', methods: ['POST'])]
    public function deletePost(
        PostRepository $postRepository,
        Request $request,
        int $postId
    ): Response {
        $post = $postRepository->find($postId);
        if (!$post) {
            throw $this->createNotFoundException('Post not found');
        }
        if ($post->getUser() !== $this->getUser()) {
            throw new AccessDeniedException('Vous ne pouvez supprimer que vos propres posts.');
        }

        // Protection CSRF
        if (!$this->isCsrfTokenValid('delete_post_' . $postId, $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token');
        }

        $category = $post->getForum()->getTitle();
        $postRepository->removePost($post);

        return $this->redirectToRoute('app_forums', [
            'category' => $category,
        ]);
    }

    #[Route('forums/posts/data/{postId}', name: 'app_post_data', methods: ['GET'])]
    public function getPostData(
        PostRepository $postRepository,
        CommentRepository $commentRepository,
        UserLikeRepository $userLikeRepository,
        int $postId
    ): Response {
        $post = $postRepository->find($postId);
        if (!$post) {
            return $this->json(['error' => 'Post not found'], 404);
        }

        $comments = $commentRepository->findByPost($postId);
        $likes = [];
        foreach ($comments as $comment) {
            $likes[] = [
                'commentId' => $comment->getId(),
                'likeCount' => $userLikeRepository->countByCommentId($comment->getId()),
            ];
        }

        return $this->json([
            'post' => [
                'id' => $post->getId(),
                'name' => $post->getName(),
                'description' => $post->getDescription(),
                'creationDate' => $post->getCreationDate()->format('Y-m-d H:i:s'),
                'lastActivity' => $post->getLastActivity()->format('Y-m-d H:i:s'),
                'forumId' => $post->getForum() ? $post->getForum()->getId() : null, // <-- Ajouté
            ],
            'comments' => $comments,
            'likes' => $likes,
        ]);
    }

    #[Route('/forums/comments/{id}/likes', name: 'app_comment_likes_count', methods: ['GET'])]
    public function getCommentLikesCount(
        ?Comment $comment,
        UserLikeRepository $userLikeRepository
    ): Response {
        if (!$comment) {
            return $this->json(['error' => 'Comment not found'], 404);
        }
        $count = $userLikeRepository->countByCommentId($comment->getId());
        return $this->json(['count' => $count]);
    }
}