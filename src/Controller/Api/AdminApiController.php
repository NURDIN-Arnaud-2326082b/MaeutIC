<?php

namespace App\Controller\Api;

use App\Entity\Tag;
use App\Repository\TagRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/admin')]
class AdminApiController extends AbstractController
{
    /**
     * Get all tags, optionally filtered by search query
     */
    #[Route('/tags', name: 'api_admin_tags_list', methods: ['GET'])]
    public function getTags(Request $request, TagRepository $tagRepository): JsonResponse
    {
        /** @var \App\Entity\User|null $user */
        $user = $this->getUser();
        if (!$user || $user->getUserType() !== 1) {
            return $this->json(['error' => 'Accès refusé'], Response::HTTP_FORBIDDEN);
        }

        $search = $request->query->get('search', '');

        if (empty(trim($search))) {
            $tags = $tagRepository->findAllOrderedByName();
        } else {
            $tags = $tagRepository->findByName($search);
        }

        $data = array_map(function($tag) {
            return [
                'id' => $tag->getId(),
                'name' => $tag->getName(),
            ];
        }, $tags);

        return $this->json(['tags' => $data]);
    }

    /**
     * Create a new tag
     */
    #[Route('/tags', name: 'api_admin_tags_create', methods: ['POST'])]
    public function createTag(
        Request $request,
        TagRepository $tagRepository,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        /** @var \App\Entity\User|null $user */
        $user = $this->getUser();
        if (!$user || $user->getUserType() !== 1) {
            return $this->json(['error' => 'Accès refusé'], Response::HTTP_FORBIDDEN);
        }

        $data = json_decode($request->getContent(), true);

        if (!isset($data['name']) || empty(trim($data['name']))) {
            return $this->json(['error' => 'Le nom du tag est requis'], Response::HTTP_BAD_REQUEST);
        }

        $name = trim($data['name']);

        // Check if tag already exists
        $existingTag = $tagRepository->findOneBy(['name' => $name]);
        if ($existingTag) {
            return $this->json(['error' => 'Un tag avec ce nom existe déjà'], Response::HTTP_CONFLICT);
        }

        $tag = new Tag();
        $tag->setName($name);

        $entityManager->persist($tag);
        $entityManager->flush();

        return $this->json([
            'message' => 'Tag créé avec succès',
            'tag' => [
                'id' => $tag->getId(),
                'name' => $tag->getName(),
            ]
        ], Response::HTTP_CREATED);
    }

    /**
     * Update a tag
     */
    #[Route('/tags/{id}', name: 'api_admin_tags_update', methods: ['PUT', 'PATCH'])]
    public function updateTag(
        int $id,
        Request $request,
        TagRepository $tagRepository,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        /** @var \App\Entity\User|null $user */
        $user = $this->getUser();
        if (!$user || $user->getUserType() !== 1) {
            return $this->json(['error' => 'Accès refusé'], Response::HTTP_FORBIDDEN);
        }

        $tag = $tagRepository->find($id);
        if (!$tag) {
            return $this->json(['error' => 'Tag non trouvé'], Response::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true);

        if (!isset($data['name']) || empty(trim($data['name']))) {
            return $this->json(['error' => 'Le nom du tag est requis'], Response::HTTP_BAD_REQUEST);
        }

        $name = trim($data['name']);

        // Check if another tag with this name already exists
        $existingTag = $tagRepository->findOneBy(['name' => $name]);
        if ($existingTag && $existingTag->getId() !== $tag->getId()) {
            return $this->json(['error' => 'Un tag avec ce nom existe déjà'], Response::HTTP_CONFLICT);
        }

        $tag->setName($name);
        $entityManager->flush();

        return $this->json([
            'message' => 'Tag modifié avec succès',
            'tag' => [
                'id' => $tag->getId(),
                'name' => $tag->getName(),
            ]
        ]);
    }

    /**
     * Delete a tag
     */
    #[Route('/tags/{id}', name: 'api_admin_tags_delete', methods: ['DELETE'])]
    public function deleteTag(
        int $id,
        TagRepository $tagRepository,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        /** @var \App\Entity\User|null $user */
        $user = $this->getUser();
        if (!$user || $user->getUserType() !== 1) {
            return $this->json(['error' => 'Accès refusé'], Response::HTTP_FORBIDDEN);
        }

        $tag = $tagRepository->find($id);
        if (!$tag) {
            return $this->json(['error' => 'Tag non trouvé'], Response::HTTP_NOT_FOUND);
        }

        $entityManager->remove($tag);
        $entityManager->flush();

        return $this->json(['message' => 'Tag supprimé avec succès']);
    }
}
