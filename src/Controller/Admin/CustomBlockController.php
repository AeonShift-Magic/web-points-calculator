<?php

declare(strict_types = 1);

namespace App\Controller\Admin;

use App\Entity\CustomBlock;
use App\Form\Admin\AdminCustomBlockType;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('/admin/custom-block')]
final class CustomBlockController extends AbstractController
{
    #[Route('/{id}/delete', name: 'admin_custom_block_delete', methods: ['POST'], priority: -10)]
    public function delete(#[MapEntity(id: 'id')] CustomBlock $customBlock, EntityManagerInterface $entityManager): Response
    {
        $entityManager->remove($customBlock);
        $entityManager->flush();

        return $this->redirectToRoute('admin_custom_block_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/{id}/edit', name: 'admin_custom_block_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, #[MapEntity(id: 'id')] CustomBlock $customBlock, EntityManagerInterface $entityManager, TranslatorInterface $translator): Response
    {
        $form = $this->createForm(AdminCustomBlockType::class, $customBlock);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            $this->addFlash('success', $translator->trans('global.savesuccess.text'));

            return $this->redirectToRoute('admin_custom_block_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render(
            'admin/custom_block/edit.html.twig',
            [
                'custom_block' => $customBlock,
                'form'         => $form,
            ],
            new Response(null, $form->isSubmitted() && ! $form->isValid() ? 422 : 200)
        );
    }

    #[Route(name: 'admin_custom_block_index', methods: ['GET'])]
    public function index(PaginatorInterface $paginator, Request $request, EntityManagerInterface $entityManager): Response
    {
        $query = $entityManager->createQuery('SELECT b FROM App\Entity\CustomBlock b order by b.blockKey DESC');

        $pagination = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            20
        );

        return $this->render('admin/custom_block/index.html.twig', [
            'custom_blocks' => $pagination,
        ]);
    }

    #[Route('/new', name: 'admin_custom_block_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, TranslatorInterface $translator): Response
    {
        $customBlock = new CustomBlock();
        $form = $this->createForm(AdminCustomBlockType::class, $customBlock);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($customBlock);
            $entityManager->flush();
            $this->addFlash('success', $translator->trans('global.savesuccess.text'));

            return $this->redirectToRoute('admin_custom_block_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render(
            'admin/custom_block/new.html.twig',
            [
                'custom_block' => $customBlock,
                'form'         => $form,
            ],
            new Response(null, $form->isSubmitted() && ! $form->isValid() ? 422 : 200)
        );
    }

    #[Route('/{id}', name: 'admin_custom_block_show', methods: ['GET'])]
    public function show(#[MapEntity(id: 'id')] CustomBlock $customBlock): Response
    {
        return $this->render('admin/custom_block/show.html.twig', [
            'custom_block' => $customBlock,
        ]);
    }
}
