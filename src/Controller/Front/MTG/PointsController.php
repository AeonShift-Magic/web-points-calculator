<?php

declare(strict_types = 1);

namespace App\Controller\Front\MTG;

use App\Entity\MTG\MTGUpdate;
use App\Repository\MTG\MTGUpdateRepository;
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
     * Home route - choose between assistance, shortcuts and calculator.
     *
     * @param MTGUpdateRepository $MTGUpdateRepository
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
                && $update->getPointsList()->getRulesModel() !== null
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
                'model_files_to_include' => $modelFilesToInclude,
            ]
        );
    }
}
