<?php

declare(strict_types = 1);

namespace App\Controller\Front;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class HomeController extends AbstractController
{
    #[Route('/home', name: 'front_home', methods: ['GET'])]
    public function home(): Response
    {
        return $this->render('front/home/home.html.twig');
    }
}
