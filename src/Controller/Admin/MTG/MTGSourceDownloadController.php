<?php

declare(strict_types = 1);

namespace App\Controller\Admin\MTG;

use App\Repository\MTG\MTGCardSourceActivityHistoryRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Process\Process;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/mtg/source-download', name: 'admin_mtg_source_download')]
final class MTGSourceDownloadController extends AbstractController
{
    #[Route(name: '_index')]
    public function index(MTGCardSourceActivityHistoryRepository $MTGCardSourceActivityHistoryRepository): Response
    {
        return $this->render('admin/mtg/source_download/index.html.twig', [
            'last_update' => $MTGCardSourceActivityHistoryRepository->getLastDownloadActivityHistory(),
        ]);
    }

    #[Route('/start', name: '_start')]
    public function start(string $projectDir, string $MTGSourceDownloadCommand): Response
    {
        $response = new StreamedResponse(function () use ($projectDir, $MTGSourceDownloadCommand): void {

            echo $this->renderView('admin/mtg/source_download/start.html.twig');
            flush();

            $process = new Process(
                [
                    'php',
                    'bin/console',
                    $MTGSourceDownloadCommand,
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
