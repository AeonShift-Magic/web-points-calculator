<?php

declare(strict_types = 1);

namespace App\Controller\Admin\MTG;

use App\Entity\MTG\MTGPointsList;
use App\Form\Admin\MTG\AdminMTGPointsListType;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('/admin/mtg/points-list')]
final class MTGPointsListController extends AbstractController
{
    #[Route('/{id}/delete', name: 'admin_mtg_points_list_delete', methods: ['POST'], priority: -10)]
    public function delete(#[MapEntity(id: 'id')] MTGPointsList $pointsList, EntityManagerInterface $entityManager, TranslatorInterface $translator): Response
    {
        $entityManager->remove($pointsList);
        $entityManager->flush();
        $this->addFlash('success', $translator->trans('global.deletesuccess.text'));

        return $this->redirectToRoute('admin_mtg_points_list_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/{id}/edit', name: 'admin_mtg_points_list_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, #[MapEntity(id: 'id')] MTGPointsList $pointsList, EntityManagerInterface $entityManager, TranslatorInterface $translator): Response
    {
        $form = $this->createForm(AdminMTGPointsListType::class, $pointsList);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            $this->addFlash('success', $translator->trans('global.savesuccess.text'));

            return $this->redirectToRoute('admin_mtg_points_list_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render(
            'admin/mtg/points_list/edit.html.twig',
            [
                'points_list' => $pointsList,
                'form'        => $form,
            ],
            new Response(null, $form->isSubmitted() && ! $form->isValid() ? 422 : 200)
        );
    }

    #[Route(name: 'admin_mtg_points_list_index', methods: ['GET'])]
    public function index(PaginatorInterface $paginator, Request $request, EntityManagerInterface $entityManager): Response
    {
        $query = $entityManager->createQuery('SELECT p FROM App\Entity\MTG\MTGPointsList p ORDER BY p.title ASC');

        $pagination = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            20
        );

        return $this->render('admin/mtg/points_list/index.html.twig', [
            'points_lists' => $pagination,
        ]);
    }

    #[Route('/new', name: 'admin_mtg_points_list_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, TranslatorInterface $translator): Response
    {
        $pointsList = new MTGPointsList();
        $form = $this->createForm(AdminMTGPointsListType::class, $pointsList);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($pointsList);
            $entityManager->flush();
            $this->addFlash('success', $translator->trans('global.savesuccess.text'));

            return $this->redirectToRoute('admin_mtg_points_list_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render(
            'admin/mtg/points_list/new.html.twig',
            [
                'points_list' => $pointsList,
                'form'        => $form,
            ],
            new Response(null, $form->isSubmitted() && ! $form->isValid() ? 422 : 200)
        );
    }

    #[Route('/{id}', name: 'admin_mtg_points_list_show', methods: ['GET'])]
    public function show(#[MapEntity(id: 'id')] MTGPointsList $pointsList): Response
    {
        return $this->render('admin/mtg/points_list/show.html.twig', [
            'points_list' => $pointsList,
        ]);
    }
}
