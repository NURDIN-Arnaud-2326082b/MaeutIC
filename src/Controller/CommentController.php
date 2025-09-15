<?php

namespace App\Controller;

use App\Entity\Comment;
use App\Entity\Post;
use App\Form\CommentFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class CommentController extends AbstractController
{
    /*#[Route('/posts/{post_id}/comments', name: 'app_comments')]
    public function index(int $post_id, EntityManagerInterface $entityManager): Response
    {
        $post = $entityManager->getRepository(Post::class)->find($post_id);

        if (!$post) {
            throw $this->createNotFoundException('Post not found');
        }

        $comments = $entityManager->getRepository(Comment::class)->findBy(['postId' => $post]);

        return $this->render('post/comments.html.twig', [
            'post' => $post,
            'comments' => $comments,
        ]);
    }*/

    #[Route('/posts/{post_id}/add-comment', name: 'app_add_comment')]
    public function addComment(int $post_id, Request $request, EntityManagerInterface $entityManager): Response
    {
        if (!$this->getUser()) {
            return $this->redirectToRoute('app_login');
        }

        $post = $entityManager->getRepository(Post::class)->find($post_id);

        if (!$post) {
            throw $this->createNotFoundException('Post not found gros con');
        }

        $comment = new Comment();
        $form = $this->createForm(CommentFormType::class, $comment);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $comment->setUser($this->getUser());
            $comment->setPost($post);
            $comment->setCreationDate(new \DateTime());

            $entityManager->persist($comment);
            $entityManager->flush();

            return $this->redirectToRoute('app_comments', ['post_id' => $post_id]);
        }

        return $this->render('post/add_comment.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
