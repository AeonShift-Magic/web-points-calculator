<?php

declare(strict_types = 1);

namespace App\Controller\Front\MTG;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * AeonShift played on top of MTG License.
 */
#[Route('/mtg')]
final class PointsController extends AbstractController
{
    /**
     * Home route - choose between assistance and shortcuts.
     *
     * @return Response
     */
    #[Route('/{slug?}', name: 'front_mtg_points_index', requirements: ['slug' => '[a-z0-9]+(?:-[a-z0-9]+)*'])]
    public function mtgPointsIndex(): Response
    {
        return $this->render('front/mtg/points/index.html.twig', [
            'controller_name' => 'PointsController',
        ]);
    }
}
