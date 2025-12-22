<?php

declare(strict_types = 1);

namespace App\Controller\Admin\MTG;

use App\Repository\MTG\MTGCardSourceActivityHistoryRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Process\Process;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/mtg/dbupate', name: 'admin_mtg_dbupdate')]
final class MTGDBUpdaterController extends AbstractController
{
    #[Route(name: '_index')]
    public function index(MTGCardSourceActivityHistoryRepository $MTGCardSourceActivityHistoryRepository): Response
    {
        return $this->render('admin/mtg/db_update/index.html.twig', [
            'last_update' => $MTGCardSourceActivityHistoryRepository->getLastDBUpdateActivityHistory(),
        ]);
    }

    #[Route('/start', name: '_start')]
    public function start(string $projectDir, string $MTGDBUpdateCommand): Response
    {
        $response = new StreamedResponse(function () use ($projectDir, $MTGDBUpdateCommand): void {

            echo $this->renderView('admin/mtg/db_update/start.html.twig');
            flush();

            $process = new Process(
                [
                    'php',
                    'bin/console',
                    $MTGDBUpdateCommand,
                    '--source=web',
                ],
                $projectDir
            );

            $process->setTimeout(null);

            $process->run(static function ($type, $buffer): void {
                echo '<script>';
                echo 'if (window.__outputReady) {';
                echo 'document.getElementById("output").textContent += ' . json_encode($buffer, \JSON_THROW_ON_ERROR) . ';';
                echo '}';
                echo '</script>';
                flush();
            });
        });

        $response->headers->set('Content-Type', 'text/html');

        return $response;
    }
}
