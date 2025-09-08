<?php

namespace App\Controller;

use App\Entity\Tag;
use App\Repository\TagRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface;
use App\Form\TagFormType;

final class AdminInterfaceController extends AbstractController
{
    private Security $security;

    public function __construct(Security $security)
    {
        $this->security = $security;
    }

    #[Route('/admin', name: 'app_admin_interface')]
    public function index(TagRepository $tagRepository, Request $request): Response
    {
        $user = $this->security->getUser();

        $tag = new Tag();
        $tagForm = $this->createForm(TagFormType::class, $tag);

        $search = $request->query->get('search', '');

        if (empty(trim($search))) {
            $tags = $tagRepository->findAllOrderedByName();
        } else {
            $tags = $tagRepository->findByName($search);
        }

        // Vérifiez si l'utilisateur est connecté et si son type est 1
        if (!$user || $user->getUserType() !== 1) {
            return $this->redirectToRoute('app_login'); // Ou utilisez throw $this->createAccessDeniedException();
        }

        return $this->render('admin_interface/index.html.twig', [
            'controller_name' => 'AdminInterfaceController',
            'tags' => $tags,
            'tagForm' => $tagForm,
        ]);
    }

    #[Route('/admin/tag/edit/{id}', name: 'app_admin_tag_edit', methods: ['POST'])]
    public function edit(Tag $tag, Request $request, EntityManagerInterface $entityManager, TagRepository $tagRepository): RedirectResponse
    {
        $name = $request->request->get('name');
    
        if (!empty(trim($name))) {
            $existingTag = $tagRepository->findOneBy(['name' => $name]);
            if ($existingTag && $existingTag->getId() !== $tag->getId()) {
                $this->addFlash('error', 'Un tag avec ce nom existe déjà.');
            } else {
                $tag->setName($name);
                $entityManager->flush();
                $this->addFlash('success', 'Le tag a été modifié avec succès.');
            }
        } else {
            $this->addFlash('error', 'Le nom du tag ne peut pas être vide.');
        }
    
        return $this->redirectToRoute('app_admin_interface');
    }

    #[Route('/admin/tag/delete/{id}', name: 'app_admin_tag_delete')]
    public function delete(Tag $tag, EntityManagerInterface $entityManager): Response
    {
        $user = $this->security->getUser();

        if (!$user || $user->getUserType() !== 1) {
            return $this->redirectToRoute('app_login');
        }

        $entityManager->remove($tag);
        $entityManager->flush();
        $this->addFlash('success', 'Le tag a été supprimé avec succès.');

        return $this->redirectToRoute('app_admin_interface');
    }

    #[Route('/admin/tag/add', name: 'app_admin_tag_add', methods: ['POST'])]
    public function add(Request $request, EntityManagerInterface $entityManager, TagRepository $tagRepository): RedirectResponse
    {
        $tag = new Tag();
        $tagForm = $this->createForm(TagFormType::class, $tag);
        $tagForm->handleRequest($request);

        if ($tagForm->isSubmitted() && $tagForm->isValid()) {
            $existingTag = $tagRepository->findOneBy(['name' => $tag->getName()]);
            if ($existingTag) {
                $this->addFlash('error', 'Un tag avec ce nom existe déjà.');
            } else {
                $entityManager->persist($tag);
                $entityManager->flush();
                $this->addFlash('success', 'Le tag a été ajouté avec succès.');
            }
        } else {
            $this->addFlash('error', 'Le nom du tag ne peut pas être vide.');
        }

        return $this->redirectToRoute('app_admin_interface');
    }
}
