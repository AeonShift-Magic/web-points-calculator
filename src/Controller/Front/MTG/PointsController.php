<?php

declare(strict_types = 1);

namespace App\Controller\Front\MTG;

use App\Entity\MTG\MTGUpdate;
use App\Repository\MTG\MTGSourceCardRepository;
use App\Repository\MTG\MTGUpdateRepository;
use Psr\Cache\InvalidArgumentException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

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
     * Home route - choose between assistance, shortcuts and calculator.
     *
     * @param MTGUpdateRepository $MTGUpdateRepository
     *
     * @throws InvalidArgumentException
     *
     * @return Response
     */
    #[Route('/{slug?}', name: 'front_mtg_points_index', requirements: ['slug' => '[a-z0-9]+(?:-[a-z0-9]+)*'])]
    public function mtgPointsIndex(MTGUpdateRepository $MTGUpdateRepository): Response
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
                $modelFilesToInclude[] = constant($update->getPointsList()->getRulesModel() . '::CALCULATOR_JS_FILE');
            }
        }

        return $this->render(
            'front/mtg/points/index.html.twig',
            [
                'updates'                => $updates,
                'card_names'             => $this->MTGSourceCardRepository->getAllCardNamesCached(),
                'model_files_to_include' => $modelFilesToInclude,
            ]
        );
    }

    /**
     * For each announcement, and for each Star Format that supports Command Zones,
     *
     * @param MTGUpdateRepository $MTGUpdateRepository
     *
     * @return Response
     */
    #[Route('/find-commander', name: 'front_mtg_find_a_commander', requirements: ['slug' => '[a-z0-9]+(?:-[a-z0-9]+)*'])]
    public function mtgFindACommander(MTGUpdateRepository $MTGUpdateRepository): Response
    {

    }
}
