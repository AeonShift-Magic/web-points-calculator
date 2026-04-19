<?php

declare(strict_types = 1);

namespace App\Controller\Admin\MTG;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/mtg/points-list-mvalue')]
final class MTGPointsListMValueController extends AbstractController
{
    #[Route(name: 'admin_mtg_points_list_mvalue_index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $filters = $request->query->all();
        $initialFiltersActive = false;

        if (! empty($filters['nameEN']) || ! empty($filters['pointsList'])) {
            $initialFiltersActive = true;
        }

        return $this->render('admin/mtg/points_list_mvalue/index.html.twig', [
            'filters'              => $filters,
            'initialFiltersActive' => $initialFiltersActive,
        ]);
    }
}
