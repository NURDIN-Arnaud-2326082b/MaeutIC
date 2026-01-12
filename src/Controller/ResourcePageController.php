<?php

/**
 * Contrôleur de gestion des ressources par page
 *
 * Ce contrôleur gère les ressources (liens, documents) pour les différentes sections :
 * - Chill (détente)
 * - Methodology (méthodologie)
 * - Administrative (administratif)
 *
 * Fonctionnalités : ajout, modification, suppression, consultation des ressources.
 * Réservé aux administrateurs pour la modification.
 */

namespace App\Controller;

use App\Entity\Resource;
use App\Form\ResourceType;
use App\Repository\ResourceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ResourcePageController extends AbstractController
{
    /**
     * Affiche la page des ressources pour une section donnée
     *
     * @param string $page La section demandée
     * @param ResourceRepository $resourceRepository Le dépôt de ressources
     * @return Response La réponse HTTP avec la vue rendue
     */
    #[Route(
        '/{page}',
        name: 'app_resource_page',
        requirements: ['page' => 'chill|methodology|administrative']
    )]
    public function index(string $page, ResourceRepository $resourceRepository): Response
    {
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

    /**
     * Ajoute une nouvelle ressource à une section donnée
     *
     * @param string $page La section où ajouter la ressource
     * @param Request $request La requête HTTP
     * @param ResourceRepository $resourceRepository Le dépôt de ressources
     * @param EntityManagerInterface $em Le gestionnaire d'entités
     * @return Response La réponse HTTP
     */
    #[Route(
        '/{page}/resource/add',
        name: 'app_resource_add',
        requirements: ['page' => 'chill|methodology|administrative'],
        methods: ['POST']
    )]
    public function add(string $page, Request $request, ResourceRepository $resourceRepository, EntityManagerInterface $em): Response
    {
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

    /**
     * Récupère les données d'une ressource spécifique en JSON
     *
     * @param string $page La section de la ressource
     * @param Resource $resource La ressource demandée
     * @return JsonResponse Les données de la ressource au format JSON
     */
    #[Route(
        '/{page}/resource/data/{id}',
        name: 'app_resource_data',
        requirements: ['page' => 'chill|methodology|administrative'],
        methods: ['GET']
    )]
    public function getResourceData(string $page, Resource $resource): JsonResponse
    {
        return $this->json([
            'title' => $resource->getTitle(),
            'description' => $resource->getDescription(),
            'link' => $resource->getLink(),
        ]);
    }

    /**
     * Modifie une ressource existante dans une section donnée
     *
     * @param string $page La section de la ressource
     * @param Resource $resource La ressource à modifier
     * @param Request $request La requête HTTP
     * @param ResourceRepository $resourceRepository Le dépôt de ressources
     * @param EntityManagerInterface $em Le gestionnaire d'entités
     * @return Response La réponse HTTP
     */
    #[Route(
        '/{page}/resource/edit/{id}',
        name: 'app_resource_edit',
        requirements: ['page' => 'chill|methodology|administrative'],
        methods: ['POST']
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

    /**
     * Supprime une ressource d'une section donnée
     *
     * @param string $page La section de la ressource
     * @param Resource $resource La ressource à supprimer
     * @param Request $request La requête HTTP
     * @param EntityManagerInterface $em Le gestionnaire d'entités
     * @return Response La réponse HTTP
     */
    #[Route(
        '/{page}/resource/delete/{id}',
        name: 'app_resource_delete',
        requirements: ['page' => 'chill|methodology|administrative'],
        methods: ['POST']
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