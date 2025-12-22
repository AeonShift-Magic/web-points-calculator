<?php

declare(strict_types = 1);

namespace App\Controller\Admin;

use App\Entity\Page;
use App\Entity\User;
use App\Form\Admin\AdminPageType;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('/admin/page')]
final class PageController extends AbstractController
{
    #[Route('/{id}/delete', name: 'admin_page_delete', methods: ['POST'], priority: -10)]
    public function delete(#[MapEntity(id: 'id')] Page $page, EntityManagerInterface $entityManager): Response
    {
        $entityManager->remove($page);
        $entityManager->flush();

        return $this->redirectToRoute('admin_page_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/{id}/edit', name: 'admin_page_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, #[MapEntity(id: 'id')] Page $page, EntityManagerInterface $entityManager, TranslatorInterface $translator): Response
    {
        $form = $this->createForm(AdminPageType::class, $page);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var User $currentUser */
            $currentUser = $this->getUser();
            $page->setUpdatedBy($currentUser);
            $entityManager->flush();
            $this->addFlash('success', $translator->trans('global.savesuccess.text'));

            return $this->redirectToRoute('admin_page_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render(
            'admin/page/edit.html.twig',
            [
                'page' => $page,
                'form' => $form,
            ],
            new Response(null, $form->isSubmitted() && ! $form->isValid() ? 422 : 200)
        );
    }

    #[Route(name: 'admin_page_index', methods: ['GET'])]
    public function index(PaginatorInterface $paginator, Request $request, EntityManagerInterface $entityManager): Response
    {
        $query = $entityManager->createQuery('SELECT p FROM App\Entity\Page p order by p.zone');

        $pagination = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            20
        );

        return $this->render('admin/page/index.html.twig', [
            'pages' => $pagination,
        ]);
    }

    #[Route('/new', name: 'admin_page_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, TranslatorInterface $translator): Response
    {
        $page = new Page();
        $page->setLanguage($request->getLocale());
        $form = $this->createForm(AdminPageType::class, $page);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var User $currentUser */
            $currentUser = $this->getUser();
            $page->setCreatedBy($currentUser);
            $page->setUpdatedBy($currentUser);
            $entityManager->persist($page);
            $entityManager->flush();
            $this->addFlash('success', $translator->trans('global.savesuccess.text'));

            return $this->redirectToRoute('admin_page_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render(
            'admin/page/new.html.twig',
            [
                'page' => $page,
                'form' => $form,
            ],
            new Response(null, $form->isSubmitted() && ! $form->isValid() ? 422 : 200)
        );
    }

    #[Route('/{id}', name: 'admin_page_show', methods: ['GET'])]
    public function show(#[MapEntity(id: 'id')] Page $page): Response
    {
        return $this->render('admin/page/show.html.twig', [
            'page' => $page,
        ]);
    }
}
