<?php

declare(strict_types = 1);

namespace App\Controller\Admin\MTG;

use App\Entity\MTG\MTGCardSourceActivityHistory;
use const DIRECTORY_SEPARATOR;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('/admin/mtg/card-source-activity-history')]
final class MTGCardSourceActivityHistoryController extends AbstractController
{
    public function __construct(private string $projectDir, private string $scryfallCardsSourceDir)
    {
    }

    #[Route('/{id}/download', name: 'admin_mtg_card_source_activity_history_download_log', methods: ['GET'])]
    public function download(#[MapEntity(id: 'id')] MTGCardSourceActivityHistory $history, TranslatorInterface $translator): Response
    {
        $channelPaths = explode('/', $history->getChannel());

        if (
            isset($channelPaths[1], $channelPaths[2])
            && $channelPaths[0] === 'scryfall'
            && $channelPaths[1] === 'defaultmtgcards'
            && in_array($channelPaths[2], ['download', 'dbupdate'], true)
        ) {
            $path = $this->projectDir . DIRECTORY_SEPARATOR . $this->scryfallCardsSourceDir . DIRECTORY_SEPARATOR . $history->getLogFilePath();

            if (file_exists($path)) {
                return new BinaryFileResponse($path)
                    ->setContentDisposition(
                        ResponseHeaderBag::DISPOSITION_ATTACHMENT,
                        basename($path)
                    );
            }
        }

        $this->addFlash('error', $translator->trans('admin.mtg.cardsourceactivityhistory.error.fileremoved'));

        return $this->redirectToRoute('admin_mtg_card_source_activity_history_index');
    }

    #[Route(name: 'admin_mtg_card_source_activity_history_index', methods: ['GET'])]
    public function index(PaginatorInterface $paginator, Request $request, EntityManagerInterface $entityManager): Response
    {
        $query = $entityManager->createQuery('SELECT h FROM App\Entity\MTG\MTGCardSourceActivityHistory h ORDER BY h.startedAt DESC');

        $pagination = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            20
        );

        return $this->render('admin/mtg/card_source_activity_history/index.html.twig', [
            'histories' => $pagination,
        ]);
    }
}
