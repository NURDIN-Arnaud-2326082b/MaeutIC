<?php

namespace App\Controller\Api;

use App\Entity\Article;
use App\Entity\Author;
use App\Entity\Book;
use App\Entity\User;
use App\Repository\ArticleRepository;
use App\Repository\AuthorRepository;
use App\Repository\BookRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/api/library')]
class LibraryApiController extends AbstractController
{
    // ==================== AUTHORS ====================

    /**
     * Get all authors
     */
    #[Route('/authors', name: 'api_library_authors', methods: ['GET'])]
    public function getAuthors(AuthorRepository $authorRepository): JsonResponse
    {
        $authors = $authorRepository->findAllOrderedByName();
        
        $authorsData = array_map(function(Author $author) {
            return [
                'id' => $author->getId(),
                'name' => $author->getName(),
                'birthYear' => $author->getBirthYear(),
                'deathYear' => $author->getDeathYear(),
                'nationality' => $author->getNationality(),
                'link' => $author->getLink(),
                'image' => $author->getImage() 
                    ? '/author_images/' . $author->getImage() 
                    : '/images/default-author.png',
                'userId' => $author->getUser()?->getId(),
                'userType' => $author->getUser()?->getUserType(),
            ];
        }, $authors);

        return new JsonResponse($authorsData);
    }

    /**
     * Get a single author
     */
    #[Route('/authors/{id}', name: 'api_library_author', methods: ['GET'])]
    public function getAuthor(Author $author): JsonResponse
    {
        return new JsonResponse([
            'id' => $author->getId(),
            'name' => $author->getName(),
            'birthYear' => $author->getBirthYear(),
            'deathYear' => $author->getDeathYear(),
            'nationality' => $author->getNationality(),
            'link' => $author->getLink(),
            'image' => $author->getImage() 
                ? '/author_images/' . $author->getImage() 
                : '/images/default-author.png',
            'userId' => $author->getUser()?->getId(),
        ]);
    }

    /**
     * Create a new author
     */
    #[Route('/authors', name: 'api_library_author_create', methods: ['POST'])]
    public function createAuthor(
        Request $request,
        EntityManagerInterface $em,
        SluggerInterface $slugger
    ): JsonResponse {
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['error' => 'Non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        $author = new Author();
        $author->setName($request->request->get('name'));
        $author->setBirthYear($request->request->get('birthYear') ? (int)$request->request->get('birthYear') : null);
        $author->setDeathYear($request->request->get('deathYear') ? (int)$request->request->get('deathYear') : null);
        $author->setNationality($request->request->get('nationality'));
        $author->setLink($request->request->get('link'));
        $author->setUser($user);

        // Handle image upload
        $imageFile = $request->files->get('image');
        if ($imageFile) {
            $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
            $safeFilename = $slugger->slug($originalFilename);
            $extension = $imageFile->guessExtension() ?: pathinfo($imageFile->getClientOriginalName(), PATHINFO_EXTENSION);
            $newFilename = $safeFilename . '-' . uniqid() . '.' . $extension;

            try {
                $imageFile->move(
                    $this->getParameter('kernel.project_dir') . '/public/author_images',
                    $newFilename
                );
                $author->setImage($newFilename);
            } catch (FileException $e) {
                return new JsonResponse(['error' => 'Erreur lors de l\'upload de l\'image'], Response::HTTP_INTERNAL_SERVER_ERROR);
            }
        }

        $em->persist($author);
        $em->flush();

        return new JsonResponse([
            'id' => $author->getId(),
            'name' => $author->getName(),
            'birthYear' => $author->getBirthYear(),
            'deathYear' => $author->getDeathYear(),
            'nationality' => $author->getNationality(),
            'link' => $author->getLink(),
            'image' => $author->getImage() 
                ? '/author_images/' . $author->getImage() 
                : '/images/default-author.png',
            'userId' => $author->getUser()?->getId(),
        ], Response::HTTP_CREATED);
    }

    /**
     * Update an author
     */
    #[Route('/authors/{id}', name: 'api_library_author_update', methods: ['POST'])]
    public function updateAuthor(
        Author $author,
        Request $request,
        EntityManagerInterface $em,
        SluggerInterface $slugger
    ): JsonResponse {
        /** @var User $user */
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['error' => 'Non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        // Check permissions
        if ($author->getUser() !== $user && $user->getUserType() !== 1) {
            return new JsonResponse(['error' => 'Non autorisé'], Response::HTTP_FORBIDDEN);
        }

        if ($request->request->has('name')) {
            $author->setName($request->request->get('name'));
        }
        if ($request->request->has('birthYear')) {
            $author->setBirthYear($request->request->get('birthYear') ? (int)$request->request->get('birthYear') : null);
        }
        if ($request->request->has('deathYear')) {
            $author->setDeathYear($request->request->get('deathYear') ? (int)$request->request->get('deathYear') : null);
        }
        if ($request->request->has('nationality')) {
            $author->setNationality($request->request->get('nationality'));
        }
        if ($request->request->has('link')) {
            $author->setLink($request->request->get('link'));
        }

        // Handle image upload
        $imageFile = $request->files->get('image');
        if ($imageFile) {
            $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
            $safeFilename = $slugger->slug($originalFilename);
            $extension = $imageFile->guessExtension() ?: pathinfo($imageFile->getClientOriginalName(), PATHINFO_EXTENSION);
            $newFilename = $safeFilename . '-' . uniqid() . '.' . $extension;

            try {
                $imageFile->move(
                    $this->getParameter('kernel.project_dir') . '/public/author_images',
                    $newFilename
                );
                $author->setImage($newFilename);
            } catch (FileException $e) {
                return new JsonResponse(['error' => 'Erreur lors de l\'upload de l\'image'], Response::HTTP_INTERNAL_SERVER_ERROR);
            }
        }

        $em->flush();

        return new JsonResponse([
            'id' => $author->getId(),
            'name' => $author->getName(),
            'birthYear' => $author->getBirthYear(),
            'deathYear' => $author->getDeathYear(),
            'nationality' => $author->getNationality(),
            'link' => $author->getLink(),
            'image' => $author->getImage() 
                ? '/author_images/' . $author->getImage() 
                : '/images/default-author.png',
            'userId' => $author->getUser()?->getId(),
        ]);
    }

    /**
     * Delete an author
     */
    #[Route('/authors/{id}', name: 'api_library_author_delete', methods: ['DELETE'])]
    public function deleteAuthor(
        Author $author,
        EntityManagerInterface $em
    ): JsonResponse {
        /** @var User $user */
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['error' => 'Non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        // Check permissions
        if ($author->getUser() !== $user && $user->getUserType() !== 1) {
            return new JsonResponse(['error' => 'Non autorisé'], Response::HTTP_FORBIDDEN);
        }

        $em->remove($author);
        $em->flush();

        return new JsonResponse(['success' => true]);
    }

    // ==================== BOOKS ====================

    /**
     * Get all books
     */
    #[Route('/books', name: 'api_library_books', methods: ['GET'])]
    public function getBooks(BookRepository $bookRepository): JsonResponse
    {
        $books = $bookRepository->findBy([], ['title' => 'ASC']);
        
        $booksData = array_map(function(Book $book) {
            return [
                'id' => $book->getId(),
                'title' => $book->getTitle(),
                'author' => $book->getAuthor(),
                'link' => $book->getLink(),
                'image' => $book->getImage() ?: '/images/default-book.png',
                'userId' => $book->getUser()?->getId(),
            ];
        }, $books);

        return new JsonResponse($booksData);
    }

    /**
     * Create a new book
     */
    #[Route('/books', name: 'api_library_book_create', methods: ['POST'])]
    public function createBook(
        Request $request,
        EntityManagerInterface $em,
        SluggerInterface $slugger
    ): JsonResponse {
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['error' => 'Non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        $book = new Book();
        $book->setTitle($request->request->get('title'));
        $book->setAuthor($request->request->get('author'));
        $book->setLink($request->request->get('link'));
        $book->setUser($user);

        // Handle image upload
        $imageFile = $request->files->get('image');
        if ($imageFile) {
            $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
            $safeFilename = $slugger->slug($originalFilename);
            $extension = $imageFile->guessExtension() ?: pathinfo($imageFile->getClientOriginalName(), PATHINFO_EXTENSION);
            $newFilename = $safeFilename . '-' . uniqid() . '.' . $extension;

            try {
                $imageFile->move(
                    $this->getParameter('kernel.project_dir') . '/public/book_images',
                    $newFilename
                );
                $book->setImage('/book_images/' . $newFilename);
            } catch (FileException $e) {
                return new JsonResponse(['error' => 'Erreur lors de l\'upload de l\'image'], Response::HTTP_INTERNAL_SERVER_ERROR);
            }
        }

        $em->persist($book);
        $em->flush();

        return new JsonResponse([
            'id' => $book->getId(),
            'title' => $book->getTitle(),
            'author' => $book->getAuthor(),
            'link' => $book->getLink(),
            'image' => $book->getImage() ?: '/images/default-book.png',
            'userId' => $book->getUser()?->getId(),
        ], Response::HTTP_CREATED);
    }

    /**
     * Update a book
     */
    #[Route('/books/{id}', name: 'api_library_book_update', methods: ['POST'])]
    public function updateBook(
        Book $book,
        Request $request,
        EntityManagerInterface $em,
        SluggerInterface $slugger
    ): JsonResponse {
        /** @var User $user */
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['error' => 'Non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        // Check permissions
        if ($book->getUser() !== $user && $user->getUserType() !== 1) {
            return new JsonResponse(['error' => 'Non autorisé'], Response::HTTP_FORBIDDEN);
        }

        if ($request->request->has('title')) {
            $book->setTitle($request->request->get('title'));
        }
        if ($request->request->has('author')) {
            $book->setAuthor($request->request->get('author'));
        }
        if ($request->request->has('link')) {
            $book->setLink($request->request->get('link'));
        }

        // Handle image upload
        $imageFile = $request->files->get('image');
        if ($imageFile) {
            $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
            $safeFilename = $slugger->slug($originalFilename);
            $extension = $imageFile->guessExtension() ?: pathinfo($imageFile->getClientOriginalName(), PATHINFO_EXTENSION);
            $newFilename = $safeFilename . '-' . uniqid() . '.' . $extension;

            try {
                $imageFile->move(
                    $this->getParameter('kernel.project_dir') . '/public/book_images',
                    $newFilename
                );
                $book->setImage('/book_images/' . $newFilename);
            } catch (FileException $e) {
                return new JsonResponse(['error' => 'Erreur lors de l\'upload de l\'image'], Response::HTTP_INTERNAL_SERVER_ERROR);
            }
        }

        $em->flush();

        return new JsonResponse([
            'id' => $book->getId(),
            'title' => $book->getTitle(),
            'author' => $book->getAuthor(),
            'link' => $book->getLink(),
            'image' => $book->getImage() ?: '/images/default-book.png',
            'userId' => $book->getUser()?->getId(),
        ]);
    }

    /**
     * Delete a book
     */
    #[Route('/books/{id}', name: 'api_library_book_delete', methods: ['DELETE'])]
    public function deleteBook(
        Book $book,
        EntityManagerInterface $em
    ): JsonResponse {
        /** @var User $user */
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['error' => 'Non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        // Check permissions
        if ($book->getUser() !== $user && $user->getUserType() !== 1) {
            return new JsonResponse(['error' => 'Non autorisé'], Response::HTTP_FORBIDDEN);
        }

        $em->remove($book);
        $em->flush();

        return new JsonResponse(['success' => true]);
    }

    // ==================== ARTICLES ====================

    /**
     * Get all articles
     */
    #[Route('/articles', name: 'api_library_articles', methods: ['GET'])]
    public function getArticles(ArticleRepository $articleRepository): JsonResponse
    {
        $articles = $articleRepository->findBy([], ['title' => 'ASC']);
        
        $articlesData = array_map(function(Article $article) {
            return [
                'id' => $article->getId(),
                'title' => $article->getTitle(),
                'author' => $article->getAuthor(),
                'link' => $article->getLink(),
                'userId' => $article->getUser()?->getId(),
            ];
        }, $articles);

        return new JsonResponse($articlesData);
    }

    /**
     * Create a new article
     */
    #[Route('/articles', name: 'api_library_article_create', methods: ['POST'])]
    public function createArticle(
        Request $request,
        EntityManagerInterface $em
    ): JsonResponse {
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['error' => 'Non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        $data = json_decode($request->getContent(), true);

        $article = new Article();
        $article->setTitle($data['title'] ?? '');
        $article->setAuthor($data['author'] ?? '');
        $article->setLink($data['link'] ?? '');
        $article->setUser($user);

        $em->persist($article);
        $em->flush();

        return new JsonResponse([
            'id' => $article->getId(),
            'title' => $article->getTitle(),
            'author' => $article->getAuthor(),
            'link' => $article->getLink(),
            'userId' => $article->getUser()?->getId(),
        ], Response::HTTP_CREATED);
    }

    /**
     * Update an article
     */
    #[Route('/articles/{id}', name: 'api_library_article_update', methods: ['PUT'])]
    public function updateArticle(
        Article $article,
        Request $request,
        EntityManagerInterface $em
    ): JsonResponse {
        /** @var User $user */
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['error' => 'Non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        // Check permissions
        if ($article->getUser() !== $user && $user->getUserType() !== 1) {
            return new JsonResponse(['error' => 'Non autorisé'], Response::HTTP_FORBIDDEN);
        }

        $data = json_decode($request->getContent(), true);

        if (isset($data['title'])) {
            $article->setTitle($data['title']);
        }
        if (isset($data['author'])) {
            $article->setAuthor($data['author']);
        }
        if (isset($data['link'])) {
            $article->setLink($data['link']);
        }

        $em->flush();

        return new JsonResponse([
            'id' => $article->getId(),
            'title' => $article->getTitle(),
            'author' => $article->getAuthor(),
            'link' => $article->getLink(),
            'userId' => $article->getUser()?->getId(),
        ]);
    }

    /**
     * Delete an article
     */
    #[Route('/articles/{id}', name: 'api_library_article_delete', methods: ['DELETE'])]
    public function deleteArticle(
        Article $article,
        EntityManagerInterface $em
    ): JsonResponse {
        /** @var User $user */
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['error' => 'Non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        // Check permissions
        if ($article->getUser() !== $user && $user->getUserType() !== 1) {
            return new JsonResponse(['error' => 'Non autorisé'], Response::HTTP_FORBIDDEN);
        }

        $em->remove($article);
        $em->flush();

        return new JsonResponse(['success' => true]);
    }
}
