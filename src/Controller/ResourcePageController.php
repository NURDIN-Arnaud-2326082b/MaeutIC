<?php
namespace App\Controller;

use App\Repository\ResourceRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Request;
use App\Entity\Resource;
use App\Form\ResourceType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

final class ResourcePageController extends AbstractController
{
    #[Route(
        '/{page}',
        name: 'app_resource_page',
        requirements: ['page' => 'chill|methodology|administrative']
    )]
    public function index(string $page, ResourceRepository $resourceRepository): Response {
        $allowedPages = ['chill', 'methodology', 'administrative'];
        if (!in_array($page, $allowedPages)) {
            throw $this->createNotFoundException();
        }
        
        $resources = $resourceRepository->findByPage($page);
        $createForm = $this->createForm(ResourceType::class);
        $editForm = $this->createForm(ResourceType::class);

        return $this->render("$page/index.html.twig", [
            'controller_name' => ucfirst($page) . 'Controller',
            'resources' => $resources,
            'createForm' => $createForm,
            'editForm' => $editForm,
            'page' => $page,
        ]);
    }

    #[Route(
        '/{page}/resource/add',
        name: 'app_resource_add',
        methods: ['POST'],
        requirements: ['page' => 'chill|methodology|administrative']
    )]
    public function add(string $page, Request $request, ResourceRepository $resourceRepository, EntityManagerInterface $em): Response {
        $allowedPages = ['chill', 'methodology', 'administrative'];
        if (!in_array($page, $allowedPages)) {
            throw $this->createNotFoundException();
        }

        $user = $this->getUser();
        if (!$user || $user->getUserType() !== 1) {
            throw $this->createAccessDeniedException();
        }

        $resource = new Resource();
        $createForm = $this->createForm(ResourceType::class, $resource);
        $editForm = $this->createForm(ResourceType::class);
        $createForm->handleRequest($request);

        if ($createForm->isSubmitted() && $createForm->isValid()) {
            $resource->setPage($page);
            $resource->setUser($user);
            $em->persist($resource);
            $em->flush();

            return $this->redirectToRoute('app_resource_page', ['page' => $page]);
        }

        // Si le formulaire n'est pas valide, on réaffiche la page avec les erreurs
        $resources = $resourceRepository->findByPage($page);
        return $this->render("$page/index.html.twig", [
            'controller_name' => ucfirst($page) . 'Controller',
            'resources' => $resources,
            'createForm' => $createForm,
            'editForm' => $editForm,
            'page' => $page,
        ]);
    }

    #[Route(
        '/{page}/resource/data/{id}',
        name: 'app_resource_data',
        methods: ['GET'],
        requirements: ['page' => 'chill|methodology|administrative']
    )]
    public function getResourceData(string $page, Resource $resource): JsonResponse
    {
        return $this->json([
            'title' => $resource->getTitle(),
            'description' => $resource->getDescription(),
            'link' => $resource->getLink(),
        ]);
    }

    #[Route(
        '/{page}/resource/edit/{id}',
        name: 'app_resource_edit',
        methods: ['POST'],
        requirements: ['page' => 'chill|methodology|administrative']
    )]
    public function edit(string $page, Resource $resource, Request $request, ResourceRepository $resourceRepository, EntityManagerInterface $em): Response
    {
        $allowedPages = ['chill', 'methodology', 'administrative'];
        if (!in_array($page, $allowedPages)) {
            throw $this->createNotFoundException();
        }

        $user = $this->getUser();
        if (!$user || $user->getUserType() !== 1) {
            return $this->redirectToRoute('app_login');
        }

        $editForm = $this->createForm(ResourceType::class, $resource);
        $createForm = $this->createForm(ResourceType::class);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $em->persist($resource);
            $em->flush();
            return $this->redirectToRoute('app_resource_page', ['page' => $page]);
        }

        $resources = $resourceRepository->findByPage($page);
        return $this->render("$page/index.html.twig", [
            'controller_name' => ucfirst($page) . 'Controller',
            'resources' => $resources,
            'createForm' => $createForm,
            'editForm' => $editForm,
            'page' => $page,
        ]);
    }

    #[Route(
        '/{page}/resource/delete/{id}',
        name: 'app_resource_delete',
        methods: ['POST'],
        requirements: ['page' => 'chill|methodology|administrative']
    )]
    public function delete(string $page, Resource $resource, Request $request, EntityManagerInterface $em): Response
    {
        $allowedPages = ['chill', 'methodology', 'administrative'];
        if (!in_array($page, $allowedPages)) {
            throw $this->createNotFoundException();
        }

        $user = $this->getUser();
        if (!$user || $user->getUserType() !== 1) {
            return $this->redirectToRoute('app_login');
        }

        if ($this->isCsrfTokenValid('delete_resource_' . $resource->getId(), $request->request->get('_token'))) {
            $em->remove($resource);
            $em->flush();
        }

        return $this->redirectToRoute('app_resource_page', ['page' => $page]);
    }
}