<?php

declare(strict_types = 1);

namespace App\Controller\Admin\MTG;

use App\Entity\MTG\MTGSourceCard;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('/admin/mtg/source-card')]
final class MTGSourceCardController extends AbstractController
{
    #[Route('/{id}/delete', name: 'admin_mtg_source_card_delete', methods: ['POST'], priority: -10)]
    public function delete(#[MapEntity(id: 'id')] MTGSourceCard $sourceCard, EntityManagerInterface $entityManager, TranslatorInterface $translator): Response
    {
        $entityManager->remove($sourceCard);
        $entityManager->flush();
        $this->addFlash('success', $translator->trans('global.deletesuccess.text'));

        return $this->redirectToRoute('admin_mtg_source_card_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route(name: 'admin_mtg_source_card_index', methods: ['GET'])]
    public function index(PaginatorInterface $paginator, Request $request, EntityManagerInterface $entityManager): Response
    {
        $query = $entityManager->createQuery('SELECT c FROM App\Entity\MTG\MTGSourceCard c ORDER BY c.nameEN ASC');

        $pagination = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            20
        );

        return $this->render('admin/mtg/source_card/index.html.twig', [
            'source_cards' => $pagination,
        ]);
    }

    #[Route('/{id}', name: 'admin_mtg_source_card_show', methods: ['GET'])]
    public function show(#[MapEntity(id: 'id')] MTGSourceCard $sourceCard): Response
    {
        return $this->render('admin/mtg/source_card/show.html.twig', [
            'source_card' => $sourceCard,
        ]);
    }
}
