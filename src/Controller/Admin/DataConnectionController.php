<?php

declare(strict_types = 1);

namespace App\Controller\Admin;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class DataConnectionController extends AbstractController
{
    #[Route('/admin/data/connection', name: 'admin_data_connection')]
    public function index(): Response
    {
        return $this->render('admin/data_connection/index.html.twig', [
            'controller_name' => 'DataConnectionController',
        ]);
    }
}
