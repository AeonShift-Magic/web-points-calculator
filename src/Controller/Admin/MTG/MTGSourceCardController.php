<?php

declare(strict_types = 1);

namespace App\Controller\Admin\MTG;

use App\Entity\MTG\MTGSourceCard;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/mtg/source-card')]
final class MTGSourceCardController extends AbstractController
{
    #[Route(name: 'admin_mtg_source_card_index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $filters = $request->query->all();
        $initialFiltersActive = false;

        if (! empty($filters['nameEN']) || array_key_exists('isCommandZoneEligible', $filters)) {
            $initialFiltersActive = true;
        }

        return $this->render('admin/mtg/source_card/index.html.twig', [
            'filters'              => $filters,
            'initialFiltersActive' => $initialFiltersActive,
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
