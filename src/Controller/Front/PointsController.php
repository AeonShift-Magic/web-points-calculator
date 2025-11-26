<?php

declare(strict_types = 1);

namespace App\Controller\Front;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class PointsController extends AbstractController
{
    #[Route('/front/points', name: 'app_front_points')]
    public function index(): Response
    {
        return $this->render('front/points/index.html.twig', [
            'controller_name' => 'PointsController',
        ]);
    }
}
