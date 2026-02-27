<?php

namespace App\Controller\Api;

use App\Entity\Resource;
use App\Repository\ResourceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/resources')]
class ResourceApiController extends AbstractController
{
    /**
     * Get all resources for a specific page
     */
    #[Route('/{page}', name: 'api_resources_list', methods: ['GET'])]
    public function getResources(string $page, ResourceRepository $resourceRepository): JsonResponse
    {
        $allowedPages = ['chill', 'methodology', 'administrative'];
        if (!in_array($page, $allowedPages)) {
            return $this->json(['error' => 'Invalid page'], Response::HTTP_BAD_REQUEST);
        }

        $resources = $resourceRepository->findByPage($page);
        
        $data = array_map(function($resource) {
            return [
                'id' => $resource->getId(),
                'title' => $resource->getTitle(),
                'description' => $resource->getDescription(),
                'link' => $resource->getLink(),
                'page' => $resource->getPage(),
            ];
        }, $resources);

        return $this->json(['resources' => $data]);
    }

    /**
     * Create a new resource
     */
    #[Route('/{page}', name: 'api_resources_create', methods: ['POST'])]
    public function createResource(
        string $page,
        Request $request,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        $allowedPages = ['chill', 'methodology', 'administrative'];
        if (!in_array($page, $allowedPages)) {
            return $this->json(['error' => 'Invalid page'], Response::HTTP_BAD_REQUEST);
        }

        /** @var \App\Entity\User|null $user */
        $user = $this->getUser();
        if (!$user || $user->getUserType() !== 1) {
            return $this->json(['error' => 'Accès refusé'], Response::HTTP_FORBIDDEN);
        }

        $data = json_decode($request->getContent(), true);

        if (!isset($data['title']) || !isset($data['link'])) {
            return $this->json(['error' => 'Title and link are required'], Response::HTTP_BAD_REQUEST);
        }

        $resource = new Resource();
        $resource->setTitle($data['title']);
        $resource->setDescription($data['description'] ?? null);
        $resource->setLink($data['link']);
        $resource->setPage($page);
        $resource->setUser($user);

        $entityManager->persist($resource);
        $entityManager->flush();

        return $this->json([
            'message' => 'Resource created successfully',
            'resource' => [
                'id' => $resource->getId(),
                'title' => $resource->getTitle(),
                'description' => $resource->getDescription(),
                'link' => $resource->getLink(),
                'page' => $resource->getPage(),
            ]
        ], Response::HTTP_CREATED);
    }

    /**
     * Update a resource
     */
    #[Route('/{page}/{id}', name: 'api_resources_update', methods: ['PUT', 'PATCH'])]
    public function updateResource(
        string $page,
        int $id,
        Request $request,
        ResourceRepository $resourceRepository,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        $allowedPages = ['chill', 'methodology', 'administrative'];
        if (!in_array($page, $allowedPages)) {
            return $this->json(['error' => 'Invalid page'], Response::HTTP_BAD_REQUEST);
        }

        /** @var \App\Entity\User|null $user */
        $user = $this->getUser();
        if (!$user || $user->getUserType() !== 1) {
            return $this->json(['error' => 'Accès refusé'], Response::HTTP_FORBIDDEN);
        }

        $resource = $resourceRepository->find($id);
        if (!$resource || $resource->getPage() !== $page) {
            return $this->json(['error' => 'Resource not found'], Response::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true);

        if (isset($data['title'])) {
            $resource->setTitle($data['title']);
        }
        if (isset($data['description'])) {
            $resource->setDescription($data['description']);
        }
        if (isset($data['link'])) {
            $resource->setLink($data['link']);
        }

        $entityManager->flush();

        return $this->json([
            'message' => 'Resource updated successfully',
            'resource' => [
                'id' => $resource->getId(),
                'title' => $resource->getTitle(),
                'description' => $resource->getDescription(),
                'link' => $resource->getLink(),
                'page' => $resource->getPage(),
            ]
        ]);
    }

    /**
     * Delete a resource
     */
    #[Route('/{page}/{id}', name: 'api_resources_delete', methods: ['DELETE'])]
    public function deleteResource(
        string $page,
        int $id,
        ResourceRepository $resourceRepository,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        $allowedPages = ['chill', 'methodology', 'administrative'];
        if (!in_array($page, $allowedPages)) {
            return $this->json(['error' => 'Invalid page'], Response::HTTP_BAD_REQUEST);
        }

        /** @var \App\Entity\User|null $user */
        $user = $this->getUser();
        if (!$user || $user->getUserType() !== 1) {
            return $this->json(['error' => 'Accès refusé'], Response::HTTP_FORBIDDEN);
        }

        $resource = $resourceRepository->find($id);
        if (!$resource || $resource->getPage() !== $page) {
            return $this->json(['error' => 'Resource not found'], Response::HTTP_NOT_FOUND);
        }

        $entityManager->remove($resource);
        $entityManager->flush();

        return $this->json(['message' => 'Resource deleted successfully']);
    }
}
