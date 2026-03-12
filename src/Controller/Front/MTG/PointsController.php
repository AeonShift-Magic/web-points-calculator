<?php

declare(strict_types = 1);

namespace App\Controller\Front\MTG;

use App\Entity\MTG\MTGUpdate;
use App\Model\AeonShift\PointsList\MTG\MTGPointsListManager;
use App\Model\AeonShift\PointsList\PointsListModelInterface;
use App\Repository\MTG\MTGSourceCardRepository;
use App\Repository\MTG\MTGUpdateRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Cache\InvalidArgumentException;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * AeonShift played on top of MTG License.
 */
#[Route('/mtg')]
final class PointsController extends AbstractController
{
    public function __construct(private readonly MTGSourceCardRepository $MTGSourceCardRepository)
    {
    }

    /**
     * Calculator route - straight to the calculator.
     *
     * @param MTGUpdateRepository $MTGUpdateRepository
     * @param MTGSourceCardRepository $MTGSourceCardRepository
     *
     * @throws InvalidArgumentException
     *
     * @return Response
     */
    #[Route('/calculator/{slug?}', name: 'front_mtg_points_calculator', requirements: ['slug' => '[a-z0-9]+(?:-[a-z0-9]+)*'])]
    public function mtgPointsCalculator(MTGUpdateRepository $MTGUpdateRepository, MTGSourceCardRepository $MTGSourceCardRepository): Response
    {
        /** @var MTGUpdate[] $updates */
        $updates = $MTGUpdateRepository->getAllPublishedMTGUpdatesByStartingDateForForms();
        $modelFilesToInclude = [];

        foreach ($MTGUpdateRepository->getAllPublishedMTGUpdatesByStartingDate() as $update) {
            if (
                $update->getPointsList() !== null
                && ! empty($update->getPointsList()->getRulesModel())
                && class_exists($update->getPointsList()->getRulesModel())
                && defined($update->getPointsList()->getRulesModel() . '::CALCULATOR_JS_FILE')
            ) {
                $modelFilesToInclude[$update->getPointsList()->getRulesModel()] = constant($update->getPointsList()->getRulesModel() . '::CALCULATOR_JS_FILE');
            }
        }

        return $this->render(
            'front/mtg/points/calculator.html.twig',
            [
                'updates'                => $updates,
                'ranking_totals'         => $MTGSourceCardRepository->getRankingTotals(),
                'commanders'             => $MTGSourceCardRepository->getAllCommanders(),
                'card_names'             => $this->MTGSourceCardRepository->getAllCardNamesCached(),
                'model_files_to_include' => $modelFilesToInclude,
            ]
        );
    }

    /**
     * Home route - choose between assistance, shortcuts and calculator.
     *
     * @return Response
     */
    #[Route(name: 'front_mtg_points_index', requirements: ['slug' => '[a-z0-9]+(?:-[a-z0-9]+)*'], priority: 10)]
    public function mtgPointsIndex(): Response
    {
        return $this->render('front/mtg/points/index.html.twig');
    }

    #[Route('/updates/pointslist/{MTGUpdate}/download', name: 'front_mtg_updates_index_update', requirements: ['MTGUpdate' => '\d+'], methods: ['GET'])]
    public function mtgPointsListDownloadUpdate(
        #[MapEntity(MTGUpdate::class)]
        MTGUpdate $MTGUpdate,
        EntityManagerInterface $entityManager,
        MTGSourceCardRepository $MTGSourceCardRepository,
        Security $security,
        TranslatorInterface $translator,
        MTGUpdateRepository $MTGUpdateRepository,
        CacheInterface $pool
    ): Response
    {
        if ($MTGUpdate->isPublic() === false || $MTGUpdate->getPointsList() === null) {
            throw $this->createNotFoundException();
        }

        $pointsListModelClass = $MTGUpdate->getPointsList()->getRulesModel();

        if (! class_exists($pointsListModelClass)) {
            throw $this->createNotFoundException('Given Points List doesn\'have a valid Rules Model.');
        }

        /** @var PointsListModelInterface $pointsListModel */
        $pointsListModel = new $pointsListModelClass(
            $entityManager,
            $translator,
            $MTGSourceCardRepository,
            $security,
            $MTGUpdateRepository,
            $pool
        );

        return $pointsListModel->generateCSVResponseForList($MTGUpdate->getPointsList());
    }

    #[Route('/updates', name: 'front_mtg_updates_index', methods: ['GET'])]
    public function mtgPointsListUpdates(MTGUpdateRepository $MTGUpdateRepository): Response
    {
        $updates = $MTGUpdateRepository->getAllPublishedMTGUpdatesByStartingDate();

        if (count($updates) === 0) {
            throw $this->createNotFoundException();
        }

        return $this->render(
            'front/mtg/points/updates.html.twig',
            [
                'updates' => $updates,
            ]
        );
    }

    /**
     * Points route - View points from a list.
     *
     * @param MTGUpdate $MTGUpdate
     * @param MTGPointsListManager $MTGPointsListManager
     *
     * @throws InvalidArgumentException
     *
     * @return Response
     */
    #[Route('/updates/pointslist/{MTGUpdate}/view', name: 'front_mtg_points_list_view', requirements: ['MTGUpdate' => '\d+'])]
    public function mtgPointsListView(
        #[MapEntity(MTGUpdate::class)]
        MTGUpdate $MTGUpdate,
        MTGPointsListManager $MTGPointsListManager,
    ): Response
    {
        if ($MTGUpdate->isPublic() === false || $MTGUpdate->getPointsList() === null) {
            throw $this->createNotFoundException();
        }

        return $this->render(
            'front/mtg/points/viewupdate.html.twig',
            [
                'update_object'     => $MTGUpdate,
                'points_list'       => $MTGUpdate->getPointsList(),
                'update_data_array' => $MTGPointsListManager->getUpdatePointListsAsArray($MTGUpdate),
            ]
        );
    }
}
