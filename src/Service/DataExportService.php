<?php

namespace App\Service;

use App\Entity\Article;
use App\Entity\Comment;
use App\Entity\Conversation;
use App\Entity\Message;
use App\Entity\Notification;
use App\Entity\Post;
use App\Entity\Report;
use App\Entity\Resource;
use App\Entity\User;
use App\Entity\UserQuestions;
use Doctrine\ORM\EntityManagerInterface;

class DataExportService
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function buildUserDataExport(User $user): array
    {
        $posts = $this->entityManager->getRepository(Post::class)->findBy(['user' => $user], ['creationDate' => 'DESC']);
        $comments = $this->entityManager->getRepository(Comment::class)->findBy(['user' => $user], ['creationDate' => 'DESC']);
        $articles = $this->entityManager->getRepository(Article::class)->findBy(['user' => $user], ['id' => 'DESC']);
        $resources = $this->entityManager->getRepository(Resource::class)->findBy(['user' => $user], ['id' => 'DESC']);
        $messages = $this->entityManager->getRepository(Message::class)->findBy(['sender' => $user], ['sentAt' => 'DESC']);
        $notifications = $this->entityManager->getRepository(Notification::class)->findBy(['recipient' => $user], ['createdAt' => 'DESC']);
        $questions = $this->entityManager->getRepository(UserQuestions::class)->findBy(['user' => $user]);
        $reports = $this->entityManager->getRepository(Report::class)->findBy(['reporter' => $user], ['createdAt' => 'DESC']);

        $conversations = $this->entityManager->getRepository(Conversation::class)->createQueryBuilder('c')
            ->where('c.user1 = :user OR c.user2 = :user')
            ->setParameter('user', $user)
            ->orderBy('c.id', 'DESC')
            ->getQuery()
            ->getResult();

        return [
            'exportedAt' => (new \DateTimeImmutable())->format('c'),
            'user' => [
                'id' => $user->getId(),
                'username' => $user->getUsername(),
                'email' => $user->getEmail(),
                'firstName' => $user->getFirstName(),
                'lastName' => $user->getLastName(),
                'affiliationLocation' => $user->getAffiliationLocation(),
                'specialization' => $user->getSpecialization(),
                'researchTopic' => $user->getResearchTopic(),
                'researcherTitle' => $user->getResearcherTitle(),
                'genre' => $user->getGenre(),
                'roles' => $user->getRoles(),
                'network' => $user->getNetwork(),
                'blocked' => $user->getBlocked(),
                'blockedBy' => $user->getBlockedBy(),
                'isBanned' => $user->isBanned(),
            ],
            'posts' => array_map(static function (Post $post): array {
                return [
                    'id' => $post->getId(),
                    'name' => $post->getName(),
                    'description' => $post->getDescription(),
                    'creationDate' => $post->getCreationDate()?->format('c'),
                    'lastActivity' => $post->getLastActivity()?->format('c'),
                    'forumId' => $post->getForum()?->getId(),
                    'forumTitle' => $post->getForum()?->getTitle(),
                    'isReply' => $post->getIsReply(),
                ];
            }, $posts),
            'comments' => array_map(static function (Comment $comment): array {
                return [
                    'id' => $comment->getId(),
                    'body' => $comment->getBody(),
                    'creationDate' => $comment->getCreationDate()?->format('c'),
                    'postId' => $comment->getPost()?->getId(),
                ];
            }, $comments),
            'articles' => array_map(static function (Article $article): array {
                return [
                    'id' => $article->getId(),
                    'title' => $article->getTitle(),
                    'link' => $article->getLink(),
                    'content' => $article->getContent(),
                    'relatedBookId' => $article->getRelatedBook()?->getId(),
                    'relatedAuthorId' => $article->getRelatedAuthor()?->getId(),
                ];
            }, $articles),
            'resources' => array_map(static function (Resource $resource): array {
                return [
                    'id' => $resource->getId(),
                    'title' => $resource->getTitle(),
                    'description' => $resource->getDescription(),
                    'link' => $resource->getLink(),
                    'page' => $resource->getPage(),
                ];
            }, $resources),
            'messages' => array_map(static function (Message $message): array {
                return [
                    'id' => $message->getId(),
                    'content' => $message->getContent(),
                    'sentAt' => $message->getSentAt()?->format('c'),
                    'conversationId' => $message->getConversation()?->getId(),
                ];
            }, $messages),
            'conversations' => array_map(static function (Conversation $conversation) use ($user): array {
                $otherParticipant = $conversation->getUser1()?->getId() === $user->getId()
                    ? $conversation->getUser2()
                    : $conversation->getUser1();

                return [
                    'id' => $conversation->getId(),
                    'user1Id' => $conversation->getUser1()?->getId(),
                    'user2Id' => $conversation->getUser2()?->getId(),
                    'otherParticipant' => $otherParticipant ? [
                        'id' => $otherParticipant->getId(),
                        'username' => $otherParticipant->getUsername(),
                    ] : null,
                ];
            }, $conversations),
            'notifications' => array_map(static function (Notification $notification): array {
                return [
                    'id' => $notification->getId(),
                    'type' => $notification->getType(),
                    'status' => $notification->getStatus(),
                    'isRead' => $notification->isRead(),
                    'createdAt' => $notification->getCreatedAt()->format('c'),
                    'data' => $notification->getData(),
                ];
            }, $notifications),
            'userQuestions' => array_map(static function (UserQuestions $question): array {
                return [
                    'id' => $question->getId(),
                    'question' => $question->getQuestion(),
                    'answer' => $question->getAnswer(),
                ];
            }, $questions),
            'reportsCreated' => array_map(static function (Report $report): array {
                return [
                    'id' => $report->getId(),
                    'targetType' => $report->getTargetType(),
                    'targetId' => $report->getTargetId(),
                    'reason' => $report->getReason(),
                    'details' => $report->getDetails(),
                    'status' => $report->getStatus(),
                    'createdAt' => $report->getCreatedAt()?->format('c'),
                ];
            }, $reports),
        ];
    }
}
