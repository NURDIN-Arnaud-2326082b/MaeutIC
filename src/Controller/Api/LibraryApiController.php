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
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/api/library')]
class LibraryApiController extends AbstractController
{
    private const ARTICLE_IMAGE_BASE_PATH = '/article_images/';
    private const ARTICLE_PDF_BASE_PATH = '/article_pdfs/';

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
            $isExternal = $this->isExternalUrl($article->getLink());

            return [
                'id' => $article->getId(),
                'title' => $article->getTitle(),
                'author' => $this->getDerivedArticleAuthor($article),
                'link' => $article->getLink(),
                'isExternal' => $isExternal,
                'content' => $article->getContent(),
                'imageUrl' => $this->getArticleImageUrl($article),
                'pdfUrl' => $this->getArticlePdfUrl($article),
                ...$this->getArticleConcernPayload($article),
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

        $data = $request->request->all();
        if (empty($data)) {
            $data = json_decode($request->getContent(), true) ?? [];
        }

        $article = new Article();
        $article->setTitle($data['title'] ?? '');
        $link = $data['link'] ?? null;
        if (is_string($link)) {
            $link = trim($link);
            if ($link === '') {
                $link = null;
            }
        }
        $article->setLink($link);
        $article->setContent($data['content'] ?? null);
        $article->setUser($user);

        $concernError = $this->applyArticleConcern($article, $data, $em);
        if ($concernError instanceof JsonResponse) {
            return $concernError;
        }

        $imageFile = $request->files->get('image');
        if ($imageFile) {
            $uploadResult = $this->uploadArticleImage($imageFile);
            if ($uploadResult['error']) {
                return new JsonResponse(['error' => $uploadResult['error']], Response::HTTP_BAD_REQUEST);
            }
            $article->setImagePath($uploadResult['filename']);
        }

        $pdfFile = $request->files->get('pdf');
        if ($pdfFile) {
            $uploadResult = $this->uploadArticlePdf($pdfFile);
            if ($uploadResult['error']) {
                return new JsonResponse(['error' => $uploadResult['error']], Response::HTTP_BAD_REQUEST);
            }
            $article->setPdfPath($uploadResult['filename']);
        }

        $em->persist($article);
        $em->flush();

        return new JsonResponse([
            'id' => $article->getId(),
            'title' => $article->getTitle(),
            'author' => $this->getDerivedArticleAuthor($article),
            'link' => $article->getLink(),
            'isExternal' => $this->isExternalUrl($article->getLink()),
            'content' => $article->getContent(),
            'imageUrl' => $this->getArticleImageUrl($article),
            'pdfUrl' => $this->getArticlePdfUrl($article),
            ...$this->getArticleConcernPayload($article),
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

        // Symfony/PHP do not reliably populate $request->request / $request->files
        // for multipart/form-data bodies sent with PUT. Reject such requests explicitly
        // to avoid silently ignoring fields/files.
        if ($request->isMethod('PUT')) {
            $contentType = (string) $request->headers->get('Content-Type', '');
            if (stripos($contentType, 'multipart/form-data') === 0) {
                return new JsonResponse(
                    [
                        'error' => 'Les requêtes PUT avec multipart/form-data ne sont pas prises en charge pour cette ressource. '
                            . 'Utilisez JSON pour les mises à jour ou une requête POST/dédiée pour les fichiers.'
                    ],
                    Response::HTTP_BAD_REQUEST
                );
            }
        }

        $data = $request->request->all();
        if (empty($data)) {
            $data = json_decode($request->getContent(), true) ?? [];
        }

        // Guard against PUT + multipart/form-data where Symfony does not populate $request->files
        $contentType = $request->headers->get('Content-Type', '');
        if (
            $request->isMethod('PUT')
            && str_starts_with($contentType, 'multipart/form-data')
            && empty($request->files->all())
        ) {
            return new JsonResponse(
                [
                    'error' => 'File uploads with multipart/form-data are not supported for PUT requests. '
                        . 'Use POST (optionally with method override) or a JSON body with separate upload endpoints.',
                ],
                Response::HTTP_BAD_REQUEST
            );
        }

        if (isset($data['title'])) {
            $article->setTitle($data['title']);
        }
        if (isset($data['link'])) {
            $article->setLink($data['link'] ?: null);
        }
        if (isset($data['content'])) {
            $article->setContent($data['content'] ?: null);
        }
        if (array_key_exists('concernType', $data) || array_key_exists('concernId', $data) || array_key_exists('bookId', $data) || array_key_exists('authorId', $data)) {
            $concernError = $this->applyArticleConcern($article, $data, $em);
            if ($concernError instanceof JsonResponse) {
                return $concernError;
            }
        }

        $removeImage = filter_var($data['removeImage'] ?? false, FILTER_VALIDATE_BOOLEAN);
        if ($removeImage && $article->getImagePath()) {
            $this->deleteArticleImageFile($article->getImagePath());
            $article->setImagePath(null);
        }

        $removePdf = filter_var($data['removePdf'] ?? false, FILTER_VALIDATE_BOOLEAN);
        if ($removePdf && $article->getPdfPath()) {
            $this->deleteArticlePdfFile($article->getPdfPath());
            $article->setPdfPath(null);
        }

        $imageFile = $request->files->get('image');
        if ($imageFile) {
            $uploadResult = $this->uploadArticleImage($imageFile);
            if ($uploadResult['error']) {
                return new JsonResponse(['error' => $uploadResult['error']], Response::HTTP_BAD_REQUEST);
            }

            if ($article->getImagePath()) {
                $this->deleteArticleImageFile($article->getImagePath());
            }

            $article->setImagePath($uploadResult['filename']);
        }

        $pdfFile = $request->files->get('pdf');
        if ($pdfFile) {
            $uploadResult = $this->uploadArticlePdf($pdfFile);
            if ($uploadResult['error']) {
                return new JsonResponse(['error' => $uploadResult['error']], Response::HTTP_BAD_REQUEST);
            }

            if ($article->getPdfPath()) {
                $this->deleteArticlePdfFile($article->getPdfPath());
            }

            $article->setPdfPath($uploadResult['filename']);
        }

        $em->flush();

        return new JsonResponse([
            'id' => $article->getId(),
            'title' => $article->getTitle(),
            'author' => $this->getDerivedArticleAuthor($article),
            'link' => $article->getLink(),
            'isExternal' => $this->isExternalUrl($article->getLink()),
            'content' => $article->getContent(),
            'imageUrl' => $this->getArticleImageUrl($article),
            'pdfUrl' => $this->getArticlePdfUrl($article),
            ...$this->getArticleConcernPayload($article),
            'userId' => $article->getUser()?->getId(),
        ]);
    }

    /**
     * @param array<string, mixed> $data
     */
    private function applyArticleConcern(
        Article $article,
        array $data,
        EntityManagerInterface $em
    ): ?JsonResponse {
        $concernType = strtolower(trim((string) ($data['concernType'] ?? '')));
        $concernId = isset($data['concernId']) ? (int) $data['concernId'] : 0;

        // Backward compatibility with previous payloads.
        if ($concernType === '') {
            if (isset($data['bookId'])) {
                $concernType = 'book';
                $concernId = (int) $data['bookId'];
            } elseif (isset($data['authorId'])) {
                $concernType = 'author';
                $concernId = (int) $data['authorId'];
            }
        }

        if ($concernType === 'book' && $concernId > 0) {
            /** @var BookRepository $bookRepository */
            $bookRepository = $em->getRepository(Book::class);
            $book = $bookRepository->find($concernId);
            if (!$book) {
                return new JsonResponse(['error' => 'Livre non trouvé'], Response::HTTP_BAD_REQUEST);
            }

            $article->setRelatedBook($book);
            $article->setRelatedAuthor(null);

            return null;
        }

        if ($concernType === 'author' && $concernId > 0) {
            /** @var AuthorRepository $authorRepository */
            $authorRepository = $em->getRepository(Author::class);
            $author = $authorRepository->find($concernId);
            if (!$author) {
                return new JsonResponse(['error' => 'Auteur non trouvé'], Response::HTTP_BAD_REQUEST);
            }

            $article->setRelatedAuthor($author);
            $article->setRelatedBook(null);

            return null;
        }

        if ($concernType === 'none' || $concernType === '' || $concernId <= 0) {
            $article->setRelatedBook(null);
            $article->setRelatedAuthor(null);

            return null;
        }

        return new JsonResponse(['error' => 'Type de cible invalide'], Response::HTTP_BAD_REQUEST);
    }

    /**
     * @return array{relatedBookId:?int,relatedBookTitle:?string,relatedAuthorId:?int,relatedAuthorName:?string,concernType:string,concernId:?int,concernLabel:?string}
     */
    private function getArticleConcernPayload(Article $article): array
    {
        $relatedBook = $article->getRelatedBook();
        $relatedAuthor = $article->getRelatedAuthor();

        $concernType = 'none';
        $concernId = null;
        $concernLabel = null;

        if ($relatedBook) {
            $concernType = 'book';
            $concernId = $relatedBook->getId();
            $concernLabel = $relatedBook->getTitle();
        } elseif ($relatedAuthor) {
            $concernType = 'author';
            $concernId = $relatedAuthor->getId();
            $concernLabel = $relatedAuthor->getName();
        }

        return [
            'relatedBookId' => $relatedBook?->getId(),
            'relatedBookTitle' => $relatedBook?->getTitle(),
            'relatedAuthorId' => $relatedAuthor?->getId(),
            'relatedAuthorName' => $relatedAuthor?->getName(),
            'concernType' => $concernType,
            'concernId' => $concernId,
            'concernLabel' => $concernLabel,
        ];
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

        if ($article->getImagePath()) {
            $this->deleteArticleImageFile($article->getImagePath());
        }

        if ($article->getPdfPath()) {
            $this->deleteArticlePdfFile($article->getPdfPath());
        }

        $em->remove($article);
        $em->flush();

        return new JsonResponse(['success' => true]);
    }

    private function isExternalUrl(?string $link): bool
    {
        return $link !== null && preg_match('/^https?:\/\//i', $link) === 1;
    }

    private function getArticleImageUrl(Article $article): ?string
    {
        if (!$article->getImagePath()) {
            return null;
        }

        return self::ARTICLE_IMAGE_BASE_PATH . $article->getImagePath();
    }

    private function getArticlePdfUrl(Article $article): ?string
    {
        if (!$article->getPdfPath()) {
            return null;
        }

        return self::ARTICLE_PDF_BASE_PATH . $article->getPdfPath();
    }

    private function getDerivedArticleAuthor(Article $article): ?string
    {
        if ($article->getRelatedAuthor()) {
            return $article->getRelatedAuthor()?->getName();
        }

        if ($article->getRelatedBook()) {
            return $article->getRelatedBook()?->getAuthor();
        }

        return null;
    }

    /**
     * @return array{filename: ?string, error: ?string}
     */
    private function uploadArticleImage(UploadedFile $imageFile): array
    {
        $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
        if (!in_array($imageFile->getMimeType(), $allowedMimeTypes, true)) {
            return ['filename' => null, 'error' => 'Format image non supporte'];
        }

        if ($imageFile->getSize() > 5 * 1024 * 1024) {
            return ['filename' => null, 'error' => 'Image trop volumineuse (max 5 MB)'];
        }

        $uploadDir = $this->getParameter('kernel.project_dir') . '/public/article_images';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0775, true);
        }

        $extension = $imageFile->guessExtension() ?: 'jpg';
        $filename = uniqid('article_img_', true) . '.' . $extension;

        try {
            $imageFile->move($uploadDir, $filename);
        } catch (FileException $e) {
            return ['filename' => null, 'error' => 'Erreur lors de l\'enregistrement de l\'image'];
        }

        return ['filename' => $filename, 'error' => null];
    }

    /**
     * @return array{filename: ?string, error: ?string}
     */
    private function uploadArticlePdf(UploadedFile $pdfFile): array
    {
        if ($pdfFile->getMimeType() !== 'application/pdf') {
            return ['filename' => null, 'error' => 'Format PDF non supporte'];
        }

        if ($pdfFile->getSize() > 10 * 1024 * 1024) {
            return ['filename' => null, 'error' => 'PDF trop volumineux (max 10 MB)'];
        }

        $uploadDir = $this->getParameter('kernel.project_dir') . '/public/article_pdfs';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0775, true);
        }

        $filename = uniqid('article_pdf_', true) . '.pdf';

        try {
            $pdfFile->move($uploadDir, $filename);
        } catch (FileException $e) {
            return ['filename' => null, 'error' => 'Erreur lors de l\'enregistrement du PDF'];
        }

        return ['filename' => $filename, 'error' => null];
    }

    private function deleteArticleImageFile(string $filename): void
    {
        $path = $this->getParameter('kernel.project_dir') . '/public/article_images/' . $filename;
        if (is_file($path)) {
            @unlink($path);
        }
    }

    private function deleteArticlePdfFile(string $filename): void
    {
        $path = $this->getParameter('kernel.project_dir') . '/public/article_pdfs/' . $filename;
        if (is_file($path)) {
            @unlink($path);
        }
    }
}
