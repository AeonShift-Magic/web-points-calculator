<?php

declare(strict_types = 1);

namespace App\Controller\Admin\MTG;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class DataImporterController extends AbstractController
{
    #[Route('/admin/data/importer', name: 'admin_data_importer')]
    public function index(): Response
    {
        return $this->render('admin/mtg/data_importer/index.html.twig', [
            'controller_name' => 'DataImporterController',
        ]);
    }
}
