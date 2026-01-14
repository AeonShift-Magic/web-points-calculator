<?php

declare(strict_types = 1);

namespace App\Controller\Admin\MTG;

use App\Entity\MTG\MTGPointsListCard;
use App\Entity\User;
use App\Form\Admin\MTG\AdminMTGPointsListCardType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('/admin/mtg/points-list-card')]
final class MTGPointsListCardController extends AbstractController
{
    #[Route('/{id}/delete', name: 'admin_mtg_points_list_card_delete', methods: ['POST'], priority: -10)]
    public function delete(#[MapEntity(id: 'id')] MTGPointsListCard $pointsListCard, EntityManagerInterface $entityManager, TranslatorInterface $translator): Response
    {
        $entityManager->remove($pointsListCard);
        $entityManager->flush();
        $this->addFlash('success', $translator->trans('global.deletesuccess.text'));

        return $this->redirectToRoute('admin_mtg_points_list_card_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/{id}/edit', name: 'admin_mtg_points_list_card_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, #[MapEntity(id: 'id')] MTGPointsListCard $pointsListCard, EntityManagerInterface $entityManager, TranslatorInterface $translator): Response
    {
        $form = $this->createForm(AdminMTGPointsListCardType::class, $pointsListCard);
        $form->handleRequest($request);

        /** @var User $currentUser */
        $currentUser = $this->getUser();

        if ($form->isSubmitted() && $form->isValid()) {
            $pointsListCard->setUpdatedBy($currentUser);
            $entityManager->flush();
            $this->addFlash('success', $translator->trans('global.savesuccess.text'));

            return $this->redirectToRoute('admin_mtg_points_list_card_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render(
            'admin/mtg/points_list_card/edit.html.twig',
            [
                'points_list_card' => $pointsListCard,
                'form'             => $form,
            ],
            new Response(null, $form->isSubmitted() && ! $form->isValid() ? 422 : 200)
        );
    }

    #[Route(name: 'admin_mtg_points_list_card_index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $filters = $request->query->all();
        $initialFiltersActive = false;

        if (! empty($filters['nameEN']) || ! empty($filters['pointsList'])) {
            $initialFiltersActive = true;
        }

        return $this->render('admin/mtg/points_list_card/index.html.twig', [
            'filters'              => $filters,
            'initialFiltersActive' => $initialFiltersActive,
        ]);
    }

    #[Route('/new', name: 'admin_mtg_points_list_card_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, TranslatorInterface $translator): Response
    {
        $pointsListCard = new MTGPointsListCard();
        $form = $this->createForm(AdminMTGPointsListCardType::class, $pointsListCard);
        $form->handleRequest($request);

        /** @var User $currentUser */
        $currentUser = $this->getUser();

        if ($form->isSubmitted() && $form->isValid()) {
            $pointsListCard->setCreatedBy($currentUser);
            $pointsListCard->setUpdatedBy($currentUser);
            $entityManager->persist($pointsListCard);
            $entityManager->flush();
            $this->addFlash('success', $translator->trans('global.savesuccess.text'));

            return $this->redirectToRoute('admin_mtg_points_list_card_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render(
            'admin/mtg/points_list_card/new.html.twig',
            [
                'points_list_card' => $pointsListCard,
                'form'             => $form,
            ],
            new Response(null, $form->isSubmitted() && ! $form->isValid() ? 422 : 200)
        );
    }

    #[Route('/{id}', name: 'admin_mtg_points_list_card_show', methods: ['GET'])]
    public function show(#[MapEntity(id: 'id')] MTGPointsListCard $pointsListCard): Response
    {
        return $this->render('admin/mtg/points_list_card/show.html.twig', [
            'points_list_card' => $pointsListCard,
        ]);
    }
}
