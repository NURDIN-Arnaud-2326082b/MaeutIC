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
use App\Repository\PostLikeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use App\Form\formType;
use App\Entity\Post;
use App\Form\PostFormType;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\HttpFoundation\JsonResponse;
use App\Entity\Conversation;
use App\Entity\User;
use App\Entity\Notification;
use App\Repository\NotificationRepository;

class ForumController extends AbstractController
{

    #[Route('/forums/search', name: 'app_forums_search', methods: ['GET'])]
    public function search(
        Request $request,
        PostRepository $postRepository
    ): JsonResponse {
        $query = $request->query->get('q', '');
        $type = $request->query->get('type', 'all');
        $dateFilter = $request->query->get('date', 'all');
        $sortBy = $request->query->get('sort', 'recent');
        $category = $request->query->get('category', 'General');

        $posts = $postRepository->searchPosts($query, $type, $dateFilter, $sortBy, $category);

        // Formater les données pour JSON
        $formattedPosts = [];
        foreach ($posts as $post) {
            $formattedPosts[] = [
                'id' => $post->getId(),
                'name' => $post->getName(),
                'description' => $post->getDescription(),
                'creation_date' => $post->getCreationDate()->format('d/m/Y'),
                'forum_title' => $post->getForum()->getTitle(),
                'author_name' => $post->getForum()->isAnonymous() 
                    ? $this->generateAnonymousId()
                    : ($post->getUser() 
                        ? $post->getUser()->getFirstName() . ' ' . $post->getUser()->getLastName()
                        : 'Ancien utilisateur'
                    )
            ];
        }

        return $this->json([
            'posts' => $formattedPosts,
            'count' => count($posts)
        ]);
    }

    // Génère 3 lettres aléatoires, un point puis 3 chiffres pour un identifiant anonyme
    public function generateAnonymousId(): string
    {
        $letters = '';
        for ($i = 0; $i < 3; $i++) {
            $letters .= chr(rand(97, 122)); // lettres minuscules a-z
        }
        $numbers = '';
        for ($i = 0; $i < 3; $i++) {
            $numbers .= rand(0, 9);
        }
        return $letters . '.' . $numbers;
    }

    #[Route('/forums/detente/search', name: 'app_detente_forums_search', methods: ['GET'])]
    public function searchDetente(
        Request $request,
        PostRepository $postRepository
    ): JsonResponse {
        return $this->handleSpecialSearch($request, $postRepository, 'detente');
    }

    #[Route('/forums/administratif/search', name: 'app_administratif_forums_search', methods: ['GET'])]
    public function searchAdministratif(
        Request $request,
        PostRepository $postRepository
    ): JsonResponse {
        return $this->handleSpecialSearch($request, $postRepository, 'administratif');
    }

    #[Route('/forums/methodology/search', name: 'app_methodology_forums_search', methods: ['GET'])]
    public function searchMethodology(
        Request $request,
        PostRepository $postRepository
    ): JsonResponse {
        return $this->handleSpecialSearch($request, $postRepository, 'methodology');
    }

    #[Route('/forums/cafe_des_lumieres/search', name: 'app_cafe_des_lumieres_forums_search', methods: ['GET'])]
    public function searchCafeDesLumieres(
        Request $request,
        PostRepository $postRepository
    ): JsonResponse {
        return $this->handleSpecialSearch($request, $postRepository, 'cafe_des_lumieres');
    }

    // Méthode privée pour gérer la recherche des catégories spéciales
    private function handleSpecialSearch(Request $request, PostRepository $postRepository, string $specialType): JsonResponse
    {
        $query = $request->query->get('q', '');
        $type = $request->query->get('type', 'all');
        $dateFilter = $request->query->get('date', 'all');
        $sortBy = $request->query->get('sort', 'recent');
        $category = $request->query->get('category', $specialType);

        // Utiliser la fonction de recherche spéciale
        $posts = $postRepository->searchSpecialPosts($query, $type, $dateFilter, $sortBy, $specialType);

        // Formater les données pour JSON
        $formattedPosts = [];
        foreach ($posts as $post) {
            $formattedPosts[] = [
                'id' => $post->getId(),
                'name' => $post->getName(),
                'description' => $post->getDescription(),
                'creation_date' => $post->getCreationDate()->format('d/m/Y'),
                'forum_title' => $post->getForum()->getTitle(),
                'author_name' => $post->getForum()->isAnonymous() 
                    ? $this->generateAnonymousId()
                    : ($post->getUser() 
                        ? $post->getUser()->getFirstName() . ' ' . $post->getUser()->getLastName()
                        : 'Ancien utilisateur'
                    )
            ];
        }

        return $this->json([
            'posts' => $formattedPosts,
            'count' => count($posts)
        ]);
    }

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
        PostLikeRepository $postLikeRepository,
        Request $request, 
        ?int $postId = null
    ): Response {
        $category = $request->attributes->get('category', 'General');
        $currentForum = $forumRepository->findOneBy(['title' => $category]);
        $forums = $forumRepository->findAll();

        if ($category === 'General') {
            $posts = $postRepository->findAllOrderedByName();
        } else {
            $posts = $postRepository->findByForum($category);
        }

        // Retirer les posts dont l'auteur est bloqué / nous a bloqué
        $posts = $this->filterPostsByBlock($posts, $this->getUser());

        // Initialiser les données de likes pour TOUS les posts (toujours)
        $postLikes = [];
        $userPostLikes = [];
        foreach ($posts as $post) {
            $postLikes[$post->getId()] = $postLikeRepository->countByPost($post);
            $userPostLikes[$post->getId()] = $this->getUser() 
                ? $postLikeRepository->isLikedByUser($post, $this->getUser()) 
                : false;
        }

        // Si un post spécifique est sélectionné
        $selectedPost = null;
        $selectedPostLikes = 0;
        $userSelectedPostLike = false;
        $comments = [];
        $likes = [];
        $userLikes = [];
        $replies = []; // Nouvelle variable pour les réponses
        $form = null;
        $editForm = null;

        // Toujours créer les formulaires
        $post = new Post();
        $form = $this->createForm(PostFormType::class, $post);
        $editForm = $this->createForm(PostFormType::class, new Post());

        if ($postId) {
            $selectedPost = $postRepository->find($postId);
            if ($selectedPost) {
                // Si l'auteur du post est dans une relation de blocage avec l'utilisateur courant -> masquer
                if ($this->isBlockedRelation($this->getUser(), $selectedPost->getUser())) {
                    $this->addFlash('error', 'Ce post est inaccessible en raison d\'un blocage.');
                    $selectedPost = null;
                } else {
                    $comments = $commentRepository->findByPost($postId);
                    $likes = [];
                    $userLikes = [];

                    // Ajouter les likes du post sélectionné
                    $selectedPostLikes = $postLikeRepository->countByPost($selectedPost);
                    $userSelectedPostLike = $this->getUser() 
                        ? $postLikeRepository->isLikedByUser($selectedPost, $this->getUser()) 
                        : false;

                    foreach ($comments as $comment) {
                        $likes[] = $userLikeRepository->countByCommentId($comment->getId());
                        $userLikes[$comment->getId()] = $this->getUser() 
                            ? $userLikeRepository->hasUserLikedComment($this->getUser()->getId(), $comment->getId()) 
                            : false;
                    }

                    // Récupérer les réponses au post
                    $replies = $postRepository->findBy(['parentPost' => $selectedPost], ['id' => 'ASC']);
                    
                    // Initialiser les likes pour les réponses
                    foreach ($replies as $reply) {
                        $postLikes[$reply->getId()] = $postLikeRepository->countByPost($reply);
                        $userPostLikes[$reply->getId()] = $this->getUser() 
                            ? $postLikeRepository->isLikedByUser($reply, $this->getUser()) 
                            : false;
                    }

                    // Traitement du formulaire de création de post
                    $form->handleRequest($request);
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

                    // Traitement des commentaires
                    if ($request->isMethod('POST') && $request->request->has('comment') && $this->getUser()) {
                        $commentBody = $request->request->get('comment');
                        if ($commentBody) {
                            // Ajouter le commentaire
                            $commentRepository->addComment($commentBody, $selectedPost, $this->getUser());
                            
                            // Notifier l'auteur du post (si pas auto-commentaire et pas de blocage)
                            $author = $selectedPost->getUser();
                            $actor = $this->getUser();
                            if ($author && $actor && $author->getId() !== $actor->getId()) {
                                if (
                                    !$author->isBlocked($actor->getId()) &&
                                    !$author->isBlockedBy($actor->getId()) &&
                                    !$actor->isBlocked($author->getId()) &&
                                    !$actor->isBlockedBy($author->getId())
                                ) {
                                    $em = $this->getDoctrine()->getManager();
                                    $notif = new Notification();
                                    $notif->setType('post_comment');
                                    $notif->setSender($actor);
                                    $notif->setRecipient($author);
                                    $notif->setStatus('unread');
                                    $notif->setData([
                                        'postId' => $selectedPost->getId(),
                                        'message' => sprintf('%s a commenté votre post', $actor->getUsername() ?? 'Quelqu\'un')
                                    ]);
                                    $em->persist($notif);
                                    $em->flush();
                                }
                            }

                            return $this->redirectToRoute('app_forums', [
                                'category' => $category,
                                'postId' => $postId,
                            ]);
                        }
                    }
                }
            }
        }

        return $this->render('forum/forums.html.twig', [
            'forums' => $forums,
            'category' => $category,
            'currentForum' => $currentForum,
            'posts' => $posts,
            'postLikes' => $postLikes,
            'userPostLikes' => $userPostLikes,
            'selectedPost' => $selectedPost,
            'selectedPostLikes' => $selectedPostLikes,
            'userSelectedPostLike' => $userSelectedPostLike,
            'comments' => $comments,
            'likes' => $likes,
            'userLikes' => $userLikes,
            'replies' => $replies ?? [], // Assurer que replies existe
            'form' => $form,
            'editForm' => $editForm,
        ]);
    }

    #[Route('/methodology-forums', name: 'app_methodology_forums')]
    #[Route('/methodology-forums/{category}', name: 'app_methodology_forums_category')]
    #[Route('/methodology-forums/{category}/{postId}', name: 'app_methodology_forums_post', requirements: ['postId' => '\d+'])]
    public function methodologyForums(
        ForumRepository $forumRepository, 
        PostRepository $postRepository, 
        CommentRepository $commentRepository, 
        UserLikeRepository $userLikeRepository,
        PostLikeRepository $postLikeRepository,
        Request $request, 
        ?string $category = null,
        ?int $postId = null
    ): Response {
        // Récupérer uniquement les forums methodology
        $forums = $forumRepository->findBy(['special' => 'methodology']);
        
        // Si aucune catégorie n'est spécifiée, afficher tous les posts methodology
        if (!$category || $category === 'methodology') {
            $category = 'methodology';
            // Récupérer les posts de tous les forums methodology
            $posts = [];
            foreach ($forums as $forum) {
                $forumPosts = $postRepository->findBy(['forum' => $forum], ['creationDate' => 'DESC']);
                $posts = array_merge($posts, $forumPosts);
            }
            $currentForum = null;
        } else {
            // Filtrer par catégorie spécifique
            $currentForum = $forumRepository->findOneBy(['title' => $category, 'special' => 'methodology']);
            if (!$currentForum) {
                throw $this->createNotFoundException('Catégorie methodology non trouvée');
            }
            $posts = $postRepository->findBy(['forum' => $currentForum], ['creationDate' => 'DESC']);
        }

        // filter posts authored by blocked users
        $posts = $this->filterPostsByBlock($posts, $this->getUser());

        // Initialiser les données de likes pour TOUS les posts
        $postLikes = [];
        $userPostLikes = [];
        foreach ($posts as $post) {
            $postLikes[$post->getId()] = $postLikeRepository->countByPost($post);
            $userPostLikes[$post->getId()] = $this->getUser() 
                ? $postLikeRepository->isLikedByUser($post, $this->getUser()) 
                : false;
        }

        // Si un post spécifique est sélectionné
        $selectedPost = null;
        $selectedPostLikes = 0;
        $userSelectedPostLike = false;
        $comments = [];
        $likes = [];
        $userLikes = [];
        $replies = [];
        $form = null;
        $editForm = null;

        // Toujours créer les formulaires
        $post = new Post();
        $form = $this->createForm(PostFormType::class, $post);
        $editForm = $this->createForm(PostFormType::class, new Post());

        if ($postId) {
            $selectedPost = $postRepository->find($postId);
            if ($selectedPost) {
                $comments = $commentRepository->findByPost($postId);
                $likes = [];
                $userLikes = [];

                // Ajouter les likes du post sélectionné
                $selectedPostLikes = $postLikeRepository->countByPost($selectedPost);
                $userSelectedPostLike = $this->getUser() 
                    ? $postLikeRepository->isLikedByUser($selectedPost, $this->getUser()) 
                    : false;

                foreach ($comments as $comment) {
                    $likes[] = $userLikeRepository->countByCommentId($comment->getId());
                    $userLikes[$comment->getId()] = $this->getUser() 
                        ? $userLikeRepository->hasUserLikedComment($this->getUser()->getId(), $comment->getId()) 
                        : false;
                }

                // Récupérer les réponses au post
                $replies = $postRepository->findBy(['parentPost' => $selectedPost], ['id' => 'ASC']);
                
                // Initialiser les likes pour les réponses
                foreach ($replies as $reply) {
                    $postLikes[$reply->getId()] = $postLikeRepository->countByPost($reply);
                    $userPostLikes[$reply->getId()] = $this->getUser() 
                        ? $postLikeRepository->isLikedByUser($reply, $this->getUser()) 
                        : false;
                }

                // Traitement du formulaire de création de post
                $form->handleRequest($request);
                if ($form->isSubmitted() && $form->isValid()) {
                    $post = $form->getData();
                    $post->setUser($this->getUser());
                    $post->setCreationDate(new \DateTime());
                    $post->setLastActivity(new \DateTime());
                    $postRepository->addPost($post);
                    
                    // Rediriger vers la page methodology avec le nouveau post
                    return $this->redirectToRoute('app_methodology_forums_post', [
                        'category' => $category,
                        'postId' => $post->getId(),
                    ]);
                }

                // Traitement des commentaires
                if ($request->isMethod('POST') && $request->request->has('comment') && $this->getUser()) {
                    $commentBody = $request->request->get('comment');
                    if ($commentBody) {
                        // Ajouter le commentaire
                        $commentRepository->addComment($commentBody, $selectedPost, $this->getUser());
                        
                        // Notifier l'auteur du post (si pas auto-commentaire et pas de blocage)
                        $author = $selectedPost->getUser();
                        $actor = $this->getUser();
                        if ($author && $actor && $author->getId() !== $actor->getId()) {
                            if (
                                !$author->isBlocked($actor->getId()) &&
                                !$author->isBlockedBy($actor->getId()) &&
                                !$actor->isBlocked($author->getId()) &&
                                !$actor->isBlockedBy($author->getId())
                            ) {
                                $em = $this->getDoctrine()->getManager();
                                $notif = new Notification();
                                $notif->setType('post_comment');
                                $notif->setSender($actor);
                                $notif->setRecipient($author);
                                $notif->setStatus('unread');
                                $notif->setData([
                                    'postId' => $selectedPost->getId(),
                                    'message' => sprintf('%s a commenté votre post', $actor->getUsername() ?? 'Quelqu\'un')
                                ]);
                                $em->persist($notif);
                                $em->flush();
                            }
                        }

                        return $this->redirectToRoute('app_methodology_forums_post', [
                            'category' => $category,
                            'postId' => $postId,
                        ]);
                    }
                }
            }
        }

        return $this->render('forum/methodology_forums.html.twig', [
            'forums' => $forums,
            'category' => $category,
            'currentForum' => $currentForum,
            'posts' => $posts,
            'postLikes' => $postLikes,
            'userPostLikes' => $userPostLikes,
            'selectedPost' => $selectedPost,
            'selectedPostLikes' => $selectedPostLikes,
            'userSelectedPostLike' => $userSelectedPostLike,
            'comments' => $comments,
            'likes' => $likes,
            'userLikes' => $userLikes,
            'replies' => $replies ?? [],
            'form' => $form,
            'editForm' => $editForm,
            'special' => 'methodology',
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

    #[Route('/methodology-forums/{category}/{postId}/edit', name: 'app_methodology_post_edit')]
    public function editMethodologyPost(
        ForumRepository $forumRepository,
        PostRepository $postRepository,
        Request $request,
        string $category,
        int $postId
    ): Response {
        $post = $postRepository->find($postId);
        if (!$post) {
            throw $this->createNotFoundException('Post not found');
        }
        if ($post->getUser() !== $this->getUser()) {
            throw new AccessDeniedException('Vous ne pouvez modifier que vos propres posts.');
        }

        // Récupérer uniquement les forums methodology
        $forums = $forumRepository->findBy(['special' => 'methodology']);
        $form = $this->createForm(PostFormType::class, $post);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $postRepository->addPost($post);
            return $this->redirectToRoute('app_methodology_forums_post', [
                'category' => $category,
                'postId' => $post->getId(),
            ]);
        }

        return $this->render('forum/edit_post.html.twig', [
            'form' => $form->createView(),
            'forums' => $forums,
            'category' => $category,
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

        // si l'utilisateur est admin il peut supprimer tous les posts
        if($this->getUser()->getUserType() !== 1) {
            if ($post->getUser() !== $this->getUser()) {
                throw new AccessDeniedException('Vous ne pouvez supprimer que vos propres posts.');
            }
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

    #[Route('/methodology-forums/{category}/delete/{postId}', name: 'app_methodology_post_delete', methods: ['POST'])]
    public function deleteMethodologyPost(
        PostRepository $postRepository,
        Request $request,
        string $category,
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

        $postRepository->removePost($post);

        // Rediriger vers la bonne catégorie
        if ($category === 'methodology') {
            return $this->redirectToRoute('app_methodology_forums');
        } else {
            return $this->redirectToRoute('app_methodology_forums_category', ['category' => $category]);
        }
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

    #[Route('forums/{category}/{postId}/reply', name: 'app_post_reply', requirements: ['postId' => '\d+'], methods: ['POST'])]
    public function replyToPost(
        int $postId,
        string $category,
        Request $request,
        PostRepository $postRepository,
        EntityManagerInterface $entityManager
    ): Response {
        if (!$this->getUser()) {
            throw $this->createAccessDeniedException('Vous devez être connecté pour répondre à un post.');
        }

        $parentPost = $postRepository->find($postId);
        if (!$parentPost) {
            throw $this->createNotFoundException('Post non trouvé.');
        }

        $replyContent = $request->request->get('reply_content');
        if (empty(trim($replyContent))) {
            $this->addFlash('error', 'Le contenu de la réponse ne peut pas être vide.');
            return $this->redirectToRoute('app_forums', ['category' => $category, 'postId' => $postId]);
        }

        $reply = new Post();
        $reply->setName('Re: ' . $parentPost->getName());
        $reply->setDescription($replyContent);
        $reply->setUser($this->getUser());
        $reply->setForum($parentPost->getForum());
        $reply->setParentPost($parentPost);
        $reply->setIsReply(true);
        $reply->setCreationDate(new \DateTime()); // Ajout de la date de création
        $reply->setLastActivity(new \DateTime()); // Ajout de la date de dernière activité

        $entityManager->persist($reply);
        $entityManager->flush();

        // Notifier l'auteur du post parent (si différent et pas de blocage)
        $author = $parentPost->getUser();
        $actor = $this->getUser();
        if ($author && $actor && $author->getId() !== $actor->getId()) {
            if (
                !$author->isBlocked($actor->getId()) &&
                !$author->isBlockedBy($actor->getId()) &&
                !$actor->isBlocked($author->getId()) &&
                !$actor->isBlockedBy($author->getId())
            ) {
                $notif = new Notification();
                $notif->setType('post_reply');
                $notif->setSender($actor);
                $notif->setRecipient($author);
                $notif->setStatus('unread');
                $notif->setData([
                    'postId' => $parentPost->getId(),
                    'replyId' => $reply->getId(),
                    'message' => sprintf('%s a répondu à votre post', $actor->getUsername() ?? 'Quelqu\'un')
                ]);
                $entityManager->persist($notif);
                $entityManager->flush();
            }
        }
+
        $this->addFlash('success', 'Votre réponse a été ajoutée avec succès.');
        return $this->redirectToRoute('app_forums', ['category' => $category, 'postId' => $postId]);
    }

    #[Route('/methodology-forums/{category}/{postId}/reply', name: 'app_methodology_post_reply', requirements: ['postId' => '\d+'], methods: ['POST'])]
    public function replyToMethodologyPost(
        string $category,
        int $postId,
        Request $request,
        PostRepository $postRepository,
        EntityManagerInterface $entityManager
    ): Response {
        if (!$this->getUser()) {
            throw $this->createAccessDeniedException('Vous devez être connecté pour répondre à un post.');
        }

        $parentPost = $postRepository->find($postId);
        if (!$parentPost) {
            throw $this->createNotFoundException('Post non trouvé.');
        }

        $replyContent = $request->request->get('reply_content');
        if (empty(trim($replyContent))) {
            $this->addFlash('error', 'Le contenu de la réponse ne peut pas être vide.');
            return $this->redirectToRoute('app_methodology_forums_post', [
                'category' => $category,
                'postId' => $postId
            ]);
        }

        $reply = new Post();
        $reply->setName('Re: ' . $parentPost->getName());
        $reply->setDescription($replyContent);
        $reply->setUser($this->getUser());
        $reply->setForum($parentPost->getForum());
        $reply->setParentPost($parentPost);
        $reply->setIsReply(true);
        $reply->setCreationDate(new \DateTime());
        $reply->setLastActivity(new \DateTime());

        $entityManager->persist($reply);
        $entityManager->flush();

        // Notifier l'auteur du post parent (si différent et pas de blocage)
        $author = $parentPost->getUser();
        $actor = $this->getUser();
        if ($author && $actor && $author->getId() !== $actor->getId()) {
            if (
                !$author->isBlocked($actor->getId()) &&
                !$author->isBlockedBy($actor->getId()) &&
                !$actor->isBlocked($author->getId()) &&
                !$actor->isBlockedBy($author->getId())
            ) {
                $notif = new Notification();
                $notif->setType('post_reply');
                $notif->setSender($actor);
                $notif->setRecipient($author);
                $notif->setStatus('unread');
                $notif->setData([
                    'postId' => $parentPost->getId(),
                    'replyId' => $reply->getId(),
                    'message' => sprintf('%s a répondu à votre post', $actor->getUsername() ?? 'Quelqu\'un')
                ]);
                $entityManager->persist($notif);
                $entityManager->flush();
            }
        }

        $this->addFlash('success', 'Votre réponse a été ajoutée avec succès.');
        return $this->redirectToRoute('app_methodology_forums_post', [
            'category' => $category,
            'postId' => $postId
        ]);
    }

    #[Route('/administratif-forums', name: 'app_administratif_forums')]
    #[Route('/administratif-forums/{category}', name: 'app_administratif_forums_category')]
    #[Route('/administratif-forums/{category}/{postId}', name: 'app_administratif_forums_post', requirements: ['postId' => '\d+'])]
    public function administratifForums(
        ForumRepository $forumRepository, 
        PostRepository $postRepository, 
        CommentRepository $commentRepository, 
        UserLikeRepository $userLikeRepository,
        PostLikeRepository $postLikeRepository,
        Request $request, 
        ?string $category = null,
        ?int $postId = null
    ): Response {
        // Récupérer uniquement les forums administratifs
        $forums = $forumRepository->findBy(['special' => 'administratif']);
        
        // Si aucune catégorie n'est spécifiée, afficher tous les posts administratifs
        if (!$category || $category === 'administratif') {
            $category = 'administratif';
            // Récupérer les posts de tous les forums administratifs
            $posts = [];
            foreach ($forums as $forum) {
                $forumPosts = $postRepository->findBy(['forum' => $forum], ['creationDate' => 'DESC']);
                $posts = array_merge($posts, $forumPosts);
            }
            $currentForum = null;
        } else {
            // Filtrer par catégorie spécifique
            $currentForum = $forumRepository->findOneBy(['title' => $category, 'special' => 'administratif']);
            if (!$currentForum) {
                throw $this->createNotFoundException('Catégorie administratif non trouvée');
            }
            $posts = $postRepository->findBy(['forum' => $currentForum], ['creationDate' => 'DESC']);
        }

        // Initialiser les données de likes pour TOUS les posts
        $postLikes = [];
        $userPostLikes = [];
        foreach ($posts as $post) {
            $postLikes[$post->getId()] = $postLikeRepository->countByPost($post);
            $userPostLikes[$post->getId()] = $this->getUser() 
                ? $postLikeRepository->isLikedByUser($post, $this->getUser()) 
                : false;
        }

        // Si un post spécifique est sélectionné
        $selectedPost = null;
        $selectedPostLikes = 0;
        $userSelectedPostLike = false;
        $comments = [];
        $likes = [];
        $userLikes = [];
        $replies = [];
        $form = null;
        $editForm = null;

        // Toujours créer les formulaires
        $post = new Post();
        $form = $this->createForm(PostFormType::class, $post);
        $editForm = $this->createForm(PostFormType::class, new Post());

        if ($postId) {
            $selectedPost = $postRepository->find($postId);
            if ($selectedPost) {
                $comments = $commentRepository->findByPost($postId);
                $likes = [];
                $userLikes = [];

                // Ajouter les likes du post sélectionné
                $selectedPostLikes = $postLikeRepository->countByPost($selectedPost);
                $userSelectedPostLike = $this->getUser() 
                    ? $postLikeRepository->isLikedByUser($selectedPost, $this->getUser()) 
                    : false;

                foreach ($comments as $comment) {
                    $likes[] = $userLikeRepository->countByCommentId($comment->getId());
                    $userLikes[$comment->getId()] = $this->getUser() 
                        ? $userLikeRepository->hasUserLikedComment($this->getUser()->getId(), $comment->getId()) 
                        : false;
                }

                // Récupérer les réponses au post
                $replies = $postRepository->findBy(['parentPost' => $selectedPost], ['id' => 'ASC']);
                
                // Initialiser les likes pour les réponses
                foreach ($replies as $reply) {
                    $postLikes[$reply->getId()] = $postLikeRepository->countByPost($reply);
                    $userPostLikes[$reply->getId()] = $this->getUser() 
                        ? $postLikeRepository->isLikedByUser($reply, $this->getUser()) 
                        : false;
                }

                // Traitement du formulaire de création de post
                $form->handleRequest($request);
                if ($form->isSubmitted() && $form->isValid()) {
                    $post = $form->getData();
                    $post->setUser($this->getUser());
                    $post->setCreationDate(new \DateTime());
                    $post->setLastActivity(new \DateTime());
                    $postRepository->addPost($post);
                    
                    // Rediriger vers la page administratif avec le nouveau post
                    return $this->redirectToRoute('app_administratif_forums_post', [
                        'category' => $category,
                        'postId' => $post->getId(),
                    ]);
                }

                // Traitement des commentaires
                if ($request->isMethod('POST') && $request->request->has('comment') && $this->getUser()) {
                    $commentBody = $request->request->get('comment');
                    if ($commentBody) {
                        // Ajouter le commentaire
                        $commentRepository->addComment($commentBody, $selectedPost, $this->getUser());
                        
                        // Notifier l'auteur du post (si pas auto-commentaire et pas de blocage)
                        $author = $selectedPost->getUser();
                        $actor = $this->getUser();
                        if ($author && $actor && $author->getId() !== $actor->getId()) {
                            if (
                                !$author->isBlocked($actor->getId()) &&
                                !$author->isBlockedBy($actor->getId()) &&
                                !$actor->isBlocked($author->getId()) &&
                                !$actor->isBlockedBy($author->getId())
                            ) {
                                $em = $this->getDoctrine()->getManager();
                                $notif = new Notification();
                                $notif->setType('post_comment');
                                $notif->setSender($actor);
                                $notif->setRecipient($author);
                                $notif->setStatus('unread');
                                $notif->setData([
                                    'postId' => $selectedPost->getId(),
                                    'message' => sprintf('%s a commenté votre post', $actor->getUsername() ?? 'Quelqu\'un')
                                ]);
                                $em->persist($notif);
                                $em->flush();
                            }
                        }

                        return $this->redirectToRoute('app_administratif_forums_post', [
                            'category' => $category,
                            'postId' => $postId,
                        ]);
                    }
                }
            }
        }

        return $this->render('forum/administratif_forums.html.twig', [
            'forums' => $forums,
            'category' => $category,
            'currentForum' => $currentForum,
            'posts' => $posts,
            'postLikes' => $postLikes,
            'userPostLikes' => $userPostLikes,
            'selectedPost' => $selectedPost,
            'selectedPostLikes' => $selectedPostLikes,
            'userSelectedPostLike' => $userSelectedPostLike,
            'comments' => $comments,
            'likes' => $likes,
            'userLikes' => $userLikes,
            'replies' => $replies ?? [],
            'form' => $form,
            'editForm' => $editForm,
            'special' => 'administratif',
        ]);
    }

    #[Route('/administratif-forums/{category}/{postId}/edit', name: 'app_administratif_post_edit')]
    public function editAdministratifPost(
        ForumRepository $forumRepository,
        PostRepository $postRepository,
        Request $request,
        string $category,
        int $postId
    ): Response {
        $post = $postRepository->find($postId);
        if (!$post) {
            throw $this->createNotFoundException('Post not found');
        }
        if ($post->getUser() !== $this->getUser()) {
            throw new AccessDeniedException('Vous ne pouvez modifier que vos propres posts.');
        }

        // Récupérer uniquement les forums administratifs
        $forums = $forumRepository->findBy(['special' => 'administratif']);
        $form = $this->createForm(PostFormType::class, $post);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $postRepository->addPost($post);
            return $this->redirectToRoute('app_administratif_forums_post', [
                'category' => $category,
                'postId' => $post->getId(),
            ]);
        }

        return $this->render('forum/edit_post.html.twig', [
            'form' => $form->createView(),
            'forums' => $forums,
            'category' => $category,
            'post' => $post,
        ]);
    }

    #[Route('/administratif-forums/{category}/delete/{postId}', name: 'app_administratif_post_delete', methods: ['POST'])]
    public function deleteAdministratifPost(
        PostRepository $postRepository,
        Request $request,
        string $category,
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

        $postRepository->removePost($post);

        // Rediriger vers la bonne catégorie
        if ($category === 'administratif') {
            return $this->redirectToRoute('app_administratif_forums');
        } else {
            return $this->redirectToRoute('app_administratif_forums_category', ['category' => $category]);
        }
    }

    #[Route('/administratif-forums/{category}/{postId}/reply', name: 'app_administratif_post_reply', requirements: ['postId' => '\d+'], methods: ['POST'])]
    public function replyToAdministratifPost(
        string $category,
        int $postId,
        Request $request,
        PostRepository $postRepository,
        EntityManagerInterface $entityManager
    ): Response {
        if (!$this->getUser()) {
            throw $this->createAccessDeniedException('Vous devez être connecté pour répondre à un post.');
        }

        $parentPost = $postRepository->find($postId);
        if (!$parentPost) {
            throw $this->createNotFoundException('Post non trouvé.');
        }

        $replyContent = $request->request->get('reply_content');
        if (empty(trim($replyContent))) {
            $this->addFlash('error', 'Le contenu de la réponse ne peut pas être vide.');
            return $this->redirectToRoute('app_administratif_forums_post', [
                'category' => $category,
                'postId' => $postId
            ]);
        }

        $reply = new Post();
        $reply->setName('Re: ' . $parentPost->getName());
        $reply->setDescription($replyContent);
        $reply->setUser($this->getUser());
        $reply->setForum($parentPost->getForum());
        $reply->setParentPost($parentPost);
        $reply->setIsReply(true);
        $reply->setCreationDate(new \DateTime());
        $reply->setLastActivity(new \DateTime());

        $entityManager->persist($reply);
        $entityManager->flush();

        // Notifier l'auteur du post parent (si différent et pas de blocage)
        $author = $parentPost->getUser();
        $actor = $this->getUser();
        if ($author && $actor && $author->getId() !== $actor->getId()) {
            if (
                !$author->isBlocked($actor->getId()) &&
                !$author->isBlockedBy($actor->getId()) &&
                !$actor->isBlocked($author->getId()) &&
                !$actor->isBlockedBy($author->getId())
            ) {
                $notif = new Notification();
                $notif->setType('post_reply');
                $notif->setSender($actor);
                $notif->setRecipient($author);
                $notif->setStatus('unread');
                $notif->setData([
                    'postId' => $parentPost->getId(),
                    'replyId' => $reply->getId(),
                    'message' => sprintf('%s a répondu à votre post', $actor->getUsername() ?? 'Quelqu\'un')
                ]);
                $entityManager->persist($notif);
                $entityManager->flush();
            }
        }

        $this->addFlash('success', 'Votre réponse a été ajoutée avec succès.');
        return $this->redirectToRoute('app_administratif_forums_post', [
            'category' => $category,
            'postId' => $postId
        ]);
    }

    #[Route('/detente-forums', name: 'app_detente_forums')]
    #[Route('/detente-forums/{category}', name: 'app_detente_forums_category')]
    #[Route('/detente-forums/{category}/{postId}', name: 'app_detente_forums_post', requirements: ['postId' => '\d+'])]
    public function detenteForums(
        ForumRepository $forumRepository, 
        PostRepository $postRepository, 
        CommentRepository $commentRepository, 
        UserLikeRepository $userLikeRepository,
        PostLikeRepository $postLikeRepository,
        Request $request, 
        ?string $category = null,
        ?int $postId = null
    ): Response {
        // Récupérer uniquement les forums détente
        $forums = $forumRepository->findBy(['special' => 'detente']);
        
        // Si aucune catégorie n'est spécifiée, afficher tous les posts détente
        if (!$category || $category === 'detente') {
            $category = 'detente';
            // Récupérer les posts de tous les forums détente
            $posts = [];
            foreach ($forums as $forum) {
                $forumPosts = $postRepository->findBy(['forum' => $forum], ['creationDate' => 'DESC']);
                $posts = array_merge($posts, $forumPosts);
            }
            $currentForum = null;
        } else {
            // Filtrer par catégorie spécifique
            $currentForum = $forumRepository->findOneBy(['title' => $category, 'special' => 'detente']);
            if (!$currentForum) {
                throw $this->createNotFoundException('Catégorie détente non trouvée');
            }
            $posts = $postRepository->findBy(['forum' => $currentForum], ['creationDate' => 'DESC']);
        }

        // Retirer les posts dont l'auteur est bloqué / nous a bloqué
        $posts = $this->filterPostsByBlock($posts, $this->getUser());

        // Initialiser les données de likes pour TOUS les posts
        $postLikes = [];
        $userPostLikes = [];
        foreach ($posts as $post) {
            $postLikes[$post->getId()] = $postLikeRepository->countByPost($post);
            $userPostLikes[$post->getId()] = $this->getUser() 
                ? $postLikeRepository->isLikedByUser($post, $this->getUser()) 
                : false;
        }

        // Si un post spécifique est sélectionné
        $selectedPost = null;
        $selectedPostLikes = 0;
        $userSelectedPostLike = false;
        $comments = [];
        $likes = [];
        $userLikes = [];
        $replies = [];
        $form = null;
        $editForm = null;

        // Toujours créer les formulaires
        $post = new Post();
        $form = $this->createForm(PostFormType::class, $post);
        $editForm = $this->createForm(PostFormType::class, new Post());

        if ($postId) {
            $selectedPost = $postRepository->find($postId);
            if ($selectedPost) {
                $comments = $commentRepository->findByPost($postId);
                $likes = [];
                $userLikes = [];

                // Ajouter les likes du post sélectionné
                $selectedPostLikes = $postLikeRepository->countByPost($selectedPost);
                $userSelectedPostLike = $this->getUser() 
                    ? $postLikeRepository->isLikedByUser($selectedPost, $this->getUser()) 
                    : false;

                foreach ($comments as $comment) {
                    $likes[] = $userLikeRepository->countByCommentId($comment->getId());
                    $userLikes[$comment->getId()] = $this->getUser() 
                        ? $userLikeRepository->hasUserLikedComment($this->getUser()->getId(), $comment->getId()) 
                        : false;
                }

                // Récupérer les réponses au post
                $replies = $postRepository->findBy(['parentPost' => $selectedPost], ['id' => 'ASC']);
                
                // Initialiser les likes pour les réponses
                foreach ($replies as $reply) {
                    $postLikes[$reply->getId()] = $postLikeRepository->countByPost($reply);
                    $userPostLikes[$reply->getId()] = $this->getUser() 
                        ? $postLikeRepository->isLikedByUser($reply, $this->getUser()) 
                        : false;
                }

                // Traitement du formulaire de création de post
                $form->handleRequest($request);
                if ($form->isSubmitted() && $form->isValid()) {
                    $post = $form->getData();
                    $post->setUser($this->getUser());
                    $post->setCreationDate(new \DateTime());
                    $post->setLastActivity(new \DateTime());
                    $postRepository->addPost($post);
                    
                    // Rediriger vers la page détente avec le nouveau post
                    return $this->redirectToRoute('app_detente_forums_post', [
                        'category' => $category,
                        'postId' => $post->getId(),
                    ]);
                }

                // Traitement des commentaires
                if ($request->isMethod('POST') && $request->request->has('comment') && $this->getUser()) {
                    $commentBody = $request->request->get('comment');
                    if ($commentBody) {
                        // Ajouter le commentaire
                        $commentRepository->addComment($commentBody, $selectedPost, $this->getUser());
                        
                        // Notifier l'auteur du post (si pas auto-commentaire et pas de blocage)
                        $author = $selectedPost->getUser();
                        $actor = $this->getUser();
                        if ($author && $actor && $author->getId() !== $actor->getId()) {
                            if (
                                !$author->isBlocked($actor->getId()) &&
                                !$author->isBlockedBy($actor->getId()) &&
                                !$actor->isBlocked($author->getId()) &&
                                !$actor->isBlockedBy($author->getId())
                            ) {
                                $em = $this->getDoctrine()->getManager();
                                $notif = new Notification();
                                $notif->setType('post_comment');
                                $notif->setSender($actor);
                                $notif->setRecipient($author);
                                $notif->setStatus('unread');
                                $notif->setData([
                                    'postId' => $selectedPost->getId(),
                                    'message' => sprintf('%s a commenté votre post', $actor->getUsername() ?? 'Quelqu\'un')
                                ]);
                                $em->persist($notif);
                                $em->flush();
                            }
                        }

                        return $this->redirectToRoute('app_detente_forums_post', [
                            'category' => $category,
                            'postId' => $postId,
                        ]);
                    }
                }
            }
        }

        return $this->render('forum/detente_forums.html.twig', [
            'forums' => $forums,
            'category' => $category,
            'currentForum' => $currentForum,
            'posts' => $posts,
            'postLikes' => $postLikes,
            'userPostLikes' => $userPostLikes,
            'selectedPost' => $selectedPost,
            'selectedPostLikes' => $selectedPostLikes,
            'userSelectedPostLike' => $userSelectedPostLike,
            'comments' => $comments,
            'likes' => $likes,
            'userLikes' => $userLikes,
            'replies' => $replies ?? [],
            'form' => $form,
            'editForm' => $editForm,
            'special' => 'detente',
        ]);
    }

    #[Route('/detente-forums/{category}/{postId}/edit', name: 'app_detente_post_edit')]
    public function editDetentePost(
        ForumRepository $forumRepository,
        PostRepository $postRepository,
        Request $request,
        string $category,
        int $postId
    ): Response {
        $post = $postRepository->find($postId);
        if (!$post) {
            throw $this->createNotFoundException('Post not found');
        }
        if ($post->getUser() !== $this->getUser()) {
            throw new AccessDeniedException('Vous ne pouvez modifier que vos propres posts.');
        }

        // Récupérer uniquement les forums détente
        $forums = $forumRepository->findBy(['special' => 'detente']);
        $form = $this->createForm(PostFormType::class, $post);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $postRepository->addPost($post);
            return $this->redirectToRoute('app_detente_forums_post', [
                'category' => $category,
                'postId' => $post->getId(),
            ]);
        }

        return $this->render('forum/edit_post.html.twig', [
            'form' => $form->createView(),
            'forums' => $forums,
            'category' => $category,
            'post' => $post,
        ]);
    }

    #[Route('/detente-forums/{category}/delete/{postId}', name: 'app_detente_post_delete', methods: ['POST'])]
    public function deleteDetentePost(
        PostRepository $postRepository,
        Request $request,
        string $category,
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

        $postRepository->removePost($post);

        // Rediriger vers la bonne catégorie
        if ($category === 'detente') {
            return $this->redirectToRoute('app_detente_forums');
        } else {
            return $this->redirectToRoute('app_detente_forums_category', ['category' => $category]);
        }
    }

    #[Route('/detente-forums/{category}/{postId}/reply', name: 'app_detente_post_reply', requirements: ['postId' => '\d+'], methods: ['POST'])]
    public function replyToDetentePost(
        string $category,
        int $postId,
        Request $request,
        PostRepository $postRepository,
        EntityManagerInterface $entityManager
    ): Response {
        if (!$this->getUser()) {
            throw $this->createAccessDeniedException('Vous devez être connecté pour répondre à un post.');
        }

        $parentPost = $postRepository->find($postId);
        if (!$parentPost) {
            throw $this->createNotFoundException('Post non trouvé.');
        }

        $replyContent = $request->request->get('reply_content');
        if (empty(trim($replyContent))) {
            $this->addFlash('error', 'Le contenu de la réponse ne peut pas être vide.');
            return $this->redirectToRoute('app_detente_forums_post', [
                'category' => $category,
                'postId' => $postId
            ]);
        }

        $reply = new Post();
        $reply->setName('Re: ' . $parentPost->getName());
        $reply->setDescription($replyContent);
        $reply->setUser($this->getUser());
        $reply->setForum($parentPost->getForum());
        $reply->setParentPost($parentPost);
        $reply->setIsReply(true);
        $reply->setCreationDate(new \DateTime());
        $reply->setLastActivity(new \DateTime());

        $entityManager->persist($reply);
        $entityManager->flush();

        // Notifier l'auteur du post parent (si différent et pas de blocage)
        $author = $parentPost->getUser();
        $actor = $this->getUser();
        if ($author && $actor && $author->getId() !== $actor->getId()) {
            if (
                !$author->isBlocked($actor->getId()) &&
                !$author->isBlockedBy($actor->getId()) &&
                !$actor->isBlocked($author->getId()) &&
                !$actor->isBlockedBy($author->getId())
            ) {
                $notif = new Notification();
                $notif->setType('post_reply');
                $notif->setSender($actor);
                $notif->setRecipient($author);
                $notif->setStatus('unread');
                $notif->setData([
                    'postId' => $parentPost->getId(),
                    'replyId' => $reply->getId(),
                    'message' => sprintf('%s a répondu à votre post', $actor->getUsername() ?? 'Quelqu\'un')
                ]);
                $entityManager->persist($notif);
                $entityManager->flush();
            }
        }

        $this->addFlash('success', 'Votre réponse a été ajoutée avec succès.');
        return $this->redirectToRoute('app_detente_forums_post', [
            'category' => $category,
            'postId' => $postId
        ]);
    }

    #[Route('/cafe_des_lumieres-forums', name: 'app_cafe_des_lumieres_forums')]
    #[Route('/cafe_des_lumieres-forums/{category}', name: 'app_cafe_des_lumieres_forums_category')]
    #[Route('/cafe_des_lumieres-forums/{category}/{postId}', name: 'app_cafe_des_lumieres_forums_post', requirements: ['postId' => '\d+'])]
    public function cafeDesLumieresForums(
        ForumRepository $forumRepository, 
        PostRepository $postRepository, 
        CommentRepository $commentRepository, 
        UserLikeRepository $userLikeRepository,
        PostLikeRepository $postLikeRepository,
        Request $request, 
        ?string $category = null,
        ?int $postId = null
    ): Response {
        // Récupérer uniquement les forums cafe_des_lumieres
        $forums = $forumRepository->findBy(['special' => 'cafe_des_lumieres']);
        
        // Si aucune catégorie n'est spécifiée, afficher tous les posts cafe_des_lumieres
        if (!$category || $category === 'cafe_des_lumieres') {
            $category = 'cafe_des_lumieres';
            // Récupérer les posts de tous les forums cafe_des_lumieres
            $posts = [];
            foreach ($forums as $forum) {
                $forumPosts = $postRepository->findBy(['forum' => $forum], ['creationDate' => 'DESC']);
                $posts = array_merge($posts, $forumPosts);
            }
            $currentForum = null;
        } else {
            // Filtrer par catégorie spécifique
            $currentForum = $forumRepository->findOneBy(['title' => $category, 'special' => 'cafe_des_lumieres']);
            if (!$currentForum) {
                throw $this->createNotFoundException('Catégorie cafe_des_lumieres non trouvée');
            }
            $posts = $postRepository->findBy(['forum' => $currentForum], ['creationDate' => 'DESC']);
        }

        // Retirer les posts dont l'auteur est bloqué / nous a bloqué
        $posts = $this->filterPostsByBlock($posts, $this->getUser());

        // Initialiser les données de likes pour TOUS les posts
        $postLikes = [];
        $userPostLikes = [];
        foreach ($posts as $post) {
            $postLikes[$post->getId()] = $postLikeRepository->countByPost($post);
            $userPostLikes[$post->getId()] = $this->getUser() 
                ? $postLikeRepository->isLikedByUser($post, $this->getUser()) 
                : false;
        }

        // Si un post spécifique est sélectionné
        $selectedPost = null;
        $selectedPostLikes = 0;
        $userSelectedPostLike = false;
        $comments = [];
        $likes = [];
        $userLikes = [];
        $replies = [];
        $form = null;
        $editForm = null;

        // Toujours créer les formulaires
        $post = new Post();
        $form = $this->createForm(PostFormType::class, $post);
        $editForm = $this->createForm(PostFormType::class, new Post());

        if ($postId) {
            $selectedPost = $postRepository->find($postId);
            if ($selectedPost) {
                $comments = $commentRepository->findByPost($postId);
                $likes = [];
                $userLikes = [];

                // Ajouter les likes du post sélectionné
                $selectedPostLikes = $postLikeRepository->countByPost($selectedPost);
                $userSelectedPostLike = $this->getUser() 
                    ? $postLikeRepository->isLikedByUser($selectedPost, $this->getUser()) 
                    : false;

                foreach ($comments as $comment) {
                    $likes[] = $userLikeRepository->countByCommentId($comment->getId());
                    $userLikes[$comment->getId()] = $this->getUser() 
                        ? $userLikeRepository->hasUserLikedComment($this->getUser()->getId(), $comment->getId()) 
                        : false;
                }

                // Récupérer les réponses au post
                $replies = $postRepository->findBy(['parentPost' => $selectedPost], ['id' => 'ASC']);
                
                // Initialiser les likes pour les réponses
                foreach ($replies as $reply) {
                    $postLikes[$reply->getId()] = $postLikeRepository->countByPost($reply);
                    $userPostLikes[$reply->getId()] = $this->getUser() 
                        ? $postLikeRepository->isLikedByUser($reply, $this->getUser()) 
                        : false;
                }

                // Traitement du formulaire de création de post
                $form->handleRequest($request);
                if ($form->isSubmitted() && $form->isValid()) {
                    $post = $form->getData();
                    $post->setUser($this->getUser());
                    $post->setCreationDate(new \DateTime());
                    $post->setLastActivity(new \DateTime());
                    $postRepository->addPost($post);
                    
                    // Rediriger vers la page cafe_des_lumieres avec le nouveau post
                    return $this->redirectToRoute('app_cafe_des_lumieres_forums_post', [
                        'category' => $category,
                        'postId' => $post->getId(),
                    ]);
                }

                // Traitement des commentaires
                if ($request->isMethod('POST') && $request->request->has('comment') && $this->getUser()) {
                    $commentBody = $request->request->get('comment');
                    if ($commentBody) {
                        // Ajouter le commentaire
                        $commentRepository->addComment($commentBody, $selectedPost, $this->getUser());
                        
                        // Notifier l'auteur du post (si pas auto-commentaire et pas de blocage)
                        $author = $selectedPost->getUser();
                        $actor = $this->getUser();
                        if ($author && $actor && $author->getId() !== $actor->getId()) {
                            if (
                                !$author->isBlocked($actor->getId()) &&
                                !$author->isBlockedBy($actor->getId()) &&
                                !$actor->isBlocked($author->getId()) &&
                                !$actor->isBlockedBy($author->getId())
                            ) {
                                $em = $this->getDoctrine()->getManager();
                                $notif = new Notification();
                                $notif->setType('post_comment');
                                $notif->setSender($actor);
                                $notif->setRecipient($author);
                                $notif->setStatus('unread');
                                $notif->setData([
                                    'postId' => $selectedPost->getId(),
                                    'message' => sprintf('%s a commenté votre post', $actor->getUsername() ?? 'Quelqu\'un')
                                ]);
                                $em->persist($notif);
                                $em->flush();
                            }
                        }

                        return $this->redirectToRoute('app_cafe_des_lumieres_forums_post', [
                            'category' => $category,
                            'postId' => $postId,
                        ]);
                    }
                }
            }
        }

        return $this->render('forum/cafe_des_lumieres_forums.html.twig', [
            'forums' => $forums,
            'category' => $category,
            'currentForum' => $currentForum,
            'posts' => $posts,
            'postLikes' => $postLikes,
            'userPostLikes' => $userPostLikes,
            'selectedPost' => $selectedPost,
            'selectedPostLikes' => $selectedPostLikes,
            'userSelectedPostLike' => $userSelectedPostLike,
            'comments' => $comments,
            'likes' => $likes,
            'userLikes' => $userLikes,
            'replies' => $replies ?? [],
            'form' => $form,
            'editForm' => $editForm,
            'special' => 'cafe_des_lumieres',
        ]);
    }

    #[Route('/cafe_des_lumieres-forums/{category}/{postId}/edit', name: 'app_cafe_des_lumieres_post_edit')]
    public function editCafeDesLumieresPost(
        ForumRepository $forumRepository,
        PostRepository $postRepository,
        Request $request,
        string $category,
        int $postId
    ): Response {
        $post = $postRepository->find($postId);
        if (!$post) {
            throw $this->createNotFoundException('Post not found');
        }
        if ($post->getUser() !== $this->getUser()) {
            throw new AccessDeniedException('Vous ne pouvez modifier que vos propres posts.');
        }

        // Récupérer uniquement les forums cafe_des_lumieres
        $forums = $forumRepository->findBy(['special' => 'cafe_des_lumieres']);
        $form = $this->createForm(PostFormType::class, $post);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $postRepository->addPost($post);
            return $this->redirectToRoute('app_cafe_des_lumieres_forums_post', [
                'category' => $category,
                'postId' => $post->getId(),
            ]);
        }

        return $this->render('forum/edit_post.html.twig', [
            'form' => $form->createView(),
            'forums' => $forums,
            'category' => $category,
            'post' => $post,
        ]);
    }

    #[Route('/cafe_des_lumieres-forums/{category}/delete/{postId}', name: 'app_cafe_des_lumieres_post_delete', methods: ['POST'])]
    public function deleteCafeDesLumieresPost(
        PostRepository $postRepository,
        Request $request,
        string $category,
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

        $postRepository->removePost($post);

        // Rediriger vers la bonne catégorie
        if ($category === 'cafe_des_lumieres') {
            return $this->redirectToRoute('app_cafe_des_lumieres_forums');


        } else {
            return $this->redirectToRoute('app_cafe_des_lumieres_forums_category', ['category' => $category]);
        }
    }

    #[Route('/cafe_des_lumieres-forums/{category}/{postId}/reply', name: 'app_cafe_des_lumieres_post_reply', requirements: ['postId' => '\d+'], methods: ['POST'])]
    public function replyToCafeDesLumieresPost(
        string $category,
        int $postId,
        Request $request,
        PostRepository $postRepository,
        EntityManagerInterface $entityManager
    ): Response {
        if (!$this->getUser()) {
            throw $this->createAccessDeniedException('Vous devez être connecté pour répondre à un post.');
        }

        $parentPost = $postRepository->find($postId);
        if (!$parentPost) {
            throw $this->createNotFoundException('Post non trouvé.');
        }

        $replyContent = $request->request->get('reply_content');
        if (empty(trim($replyContent))) {
            $this->addFlash('error', 'Le contenu de la réponse ne peut pas être vide.');
            return $this->redirectToRoute('app_cafe_des_lumieres_forums_post', [
                'category' => $category,
                'postId' => $postId
            ]);
        }

        $reply = new Post();
        $reply->setName('Re: ' . $parentPost->getName());
        $reply->setDescription($replyContent);
        $reply->setUser($this->getUser());
        $reply->setForum($parentPost->getForum());
        $reply->setParentPost($parentPost);
        $reply->setIsReply(true);
        $reply->setCreationDate(new \DateTime());
        $reply->setLastActivity(new \DateTime());

        $entityManager->persist($reply);
        $entityManager->flush();

        // Notifier l'auteur du post parent (si différent et pas de blocage)
        $author = $parentPost->getUser();
        $actor = $this->getUser();
        if ($author && $actor && $author->getId() !== $actor->getId()) {
            if (
                !$author->isBlocked($actor->getId()) &&
                !$author->isBlockedBy($actor->getId()) &&
                !$actor->isBlocked($author->getId()) &&
                !$actor->isBlockedBy($author->getId())
            ) {
                $notif = new Notification();
                $notif->setType('post_reply');
                $notif->setSender($actor);
                $notif->setRecipient($author);
                $notif->setStatus('unread');
                $notif->setData([
                    'postId' => $parentPost->getId(),
                    'replyId' => $reply->getId(),
                    'message' => sprintf('%s a répondu à votre post', $actor->getUsername() ?? 'Quelqu\'un')
                ]);
                $entityManager->persist($notif);
                $entityManager->flush();
            }
        }

        $this->addFlash('success', 'Votre réponse a été ajoutée avec succès.');
        return $this->redirectToRoute('app_cafe_des_lumieres_forums_post', [
            'category' => $category,
            'postId' => $postId
        ]);
    }

#[Route('/cafe_des_lumieres-forums/{category}/create-post', name: 'app_cafe_des_lumieres_post_create', methods: ['POST'])]
public function addCafeDesLumieresPost(
    ForumRepository $forumRepository,
    PostRepository $postRepository,
    Request $request,
    string $category
): Response {
    // Récupérer uniquement les forums cafe_des_lumieres
    $forums = $forumRepository->findBy(['special' => 'cafe_des_lumieres']);
    
    $post = new Post();
    $form = $this->createForm(PostFormType::class, $post);

    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        $post = $form->getData();
        $post->setUser($this->getUser());
        $post->setCreationDate(new \DateTime());
        $post->setLastActivity(new \DateTime());
        $postRepository->addPost($post);
        
        // Rediriger avec la catégorie du forum
        return $this->redirectToRoute('app_cafe_des_lumieres_forums_post', [
            'category' => $post->getForum()->getTitle(),
            'postId' => $post->getId(),
        ]);
    }

    // En cas d'erreur, rediriger vers la page principale
    return $this->redirectToRoute('app_cafe_des_lumieres_forums', [
        'category' => $category,
    ]);
}

#[Route('/detente-forums/{category}/create-post', name: 'app_detente_post_create', methods: ['POST'])]
public function addDetentePost(
    ForumRepository $forumRepository,
    PostRepository $postRepository,
    Request $request,
    string $category
): Response {
    // Récupérer uniquement les forums détente
    $forums = $forumRepository->findBy(['special' => 'detente']);
    
    $post = new Post();
    $form = $this->createForm(PostFormType::class, $post);

    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        $post = $form->getData();
        $post->setUser($this->getUser());
        $post->setCreationDate(new \DateTime());
        $post->setLastActivity(new \DateTime());
        $postRepository->addPost($post);
        
        // Rediriger avec la catégorie du forum
        return $this->redirectToRoute('app_detente_forums_post', [
            'category' => $post->getForum()->getTitle(),
            'postId' => $post->getId(),
        ]);
    }

    // En cas d'erreur, rediriger vers la page principale
    return $this->redirectToRoute('app_detente_forums', [
        'category' => $category,
    ]);
}

#[Route('/methodology-forums/{category}/create-post', name: 'app_methodology_post_create', methods: ['POST'])]
public function addMethodologyPost(
    ForumRepository $forumRepository,
    PostRepository $postRepository,
    Request $request,
    string $category
): Response {
    // Récupérer uniquement les forums methodology
    $forums = $forumRepository->findBy(['special' => 'methodology']);
    
    $post = new Post();
    $form = $this->createForm(PostFormType::class, $post);

    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        $post = $form->getData();
        $post->setUser($this->getUser());
        $post->setCreationDate(new \DateTime());
        $post->setLastActivity(new \DateTime());
        $postRepository->addPost($post);
        
        // Rediriger avec la catégorie du forum
        return $this->redirectToRoute('app_methodology_forums_post', [
            'category' => $post->getForum()->getTitle(),
            'postId' => $post->getId(),
        ]);
    }

    // En cas d'erreur, rediriger vers la page principale
    return $this->redirectToRoute('app_methodology_forums', [
        'category' => $category,
    ]);
}

#[Route('/administratif-forums/{category}/create-post', name: 'app_administratif_post_create', methods: ['POST'])]
public function addAdministratifPost(
    ForumRepository $forumRepository,
    PostRepository $postRepository,
    Request $request,
    string $category
): Response {
    // Récupérer uniquement les forums administratifs
    $forums = $forumRepository->findBy(['special' => 'administratif']);
    
    $post = new Post();
    $form = $this->createForm(PostFormType::class, $post);

    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        $post = $form->getData();
        $post->setUser($this->getUser());
        $post->setCreationDate(new \DateTime());
        $post->setLastActivity(new \DateTime());
        $postRepository->addPost($post);
        
        // Rediriger avec la catégorie du forum
        return $this->redirectToRoute('app_administratif_forums_post', [
            'category' => $post->getForum()->getTitle(),
            'postId' => $post->getId(),
        ]);
    }

    // En cas d'erreur, rediriger vers la page principale
    return $this->redirectToRoute('app_administratif_forums', [
        'category' => $category,
    ]);
}

    // Fonction utilitaire pour gérer l'auto-sélection du forum dans les formulaires
    private function setupForumSelection(Post $post, array $forums, string $currentCategory): void
    {
        // Si une catégorie est spécifiée et qu'elle correspond à un forum spécial, auto-sélectionner
        if ($currentCategory && $currentCategory !== 'General') {
            foreach ($forums as $forum) {
                if ($forum->getTitle() === $currentCategory) {
                    $post->setForum($forum);
                    break;
                }
 }
    }
}

    // Helper: retourne true si $currentUser bloque ou est bloqué par $otherUser
    private function isBlockedRelation(?User $currentUser, ?User $otherUser): bool
    {
        if (!$currentUser || !$otherUser) return false;
        if ($currentUser->getId() === $otherUser->getId()) return false;
        return $currentUser->isBlocked($otherUser->getId()) || $currentUser->isBlockedBy($otherUser->getId());
    }

    // Helper: filtre une liste de Post pour retirer ceux dont l'auteur est bloqué / bloque le visiteur
    private function filterPostsByBlock(array $posts, ?User $currentUser): array
    {
        if (!$currentUser) return $posts;
        $out = [];
        foreach ($posts as $p) {
            $author = $p->getUser();
            if ($author && $this->isBlockedRelation($currentUser, $author)) {
                continue;
            }
            $out[] = $p;
        }
        return array_values($out);
    }
}