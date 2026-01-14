<?php

declare(strict_types = 1);

namespace App\Twig;

use App\Model\AeonShift\Calculator\MTG\AeonShiftMTGCalculator;
use App\Repository\MTG\MTGSourceCardRepository;
use App\Repository\MTG\MTGUpdateRepository;
use const JSON_THROW_ON_ERROR;
use JsonException;
use Override;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

final class MTGCalculatorExtension extends AbstractExtension
{
    public function __construct(private AeonShiftMTGCalculator $aeonShiftMTGCalculator, private MTGUpdateRepository $MTGUpdateRepository, private MTGSourceCardRepository $MTGSourceCardRepository)
    {
    }

    #[Override]
    public function getFunctions(): array
    {
        return [
            // "is_safe" is used here, carefully!
            new TwigFunction('get_updates_and_points_lists_as_json', $this->getUpdatesAndPointsListsAsJSON(...), ['is_safe' => ['html']]),
        ];
    }

    /**
     * @throws JsonException
     *
     * @return string
     */
    public function getUpdatesAndPointsListsAsJSON(): string
    {
        $MTGUpdates = $this->MTGUpdateRepository->getAllPublishedMTGUpdatesByStartingDate();
        $outputArray = [
            'updates' => [],
        ];

        foreach ($MTGUpdates as $MTGUpdate) {
            if ($MTGUpdate->getPointsList() !== null) {
                $outputArray['updates'] = [
                    'title'                => $MTGUpdate->getTitleEN(),
                    'startingAtSimplified' => $MTGUpdate->getStartingAt()->format('Y-m-d'),
                    'endingAtSimplified'   => $MTGUpdate->getEndingAt()->format('Y-m-d'),
                    'startingAtDate'       => $MTGUpdate->getStartingAt()->format('Y-m-d\TH:i:s\Z'),
                    'endingAtDate'         => $MTGUpdate->getEndingAt()->format('Y-m-d\TH:i:s\Z'),
                    'pointsList'           => $this->aeonShiftMTGCalculator->mergeMTGSourceAndPointsList($this->MTGSourceCardRepository, $MTGUpdate->getPointsList()),
                ];
            }
        }

        return json_encode($outputArray, JSON_THROW_ON_ERROR);
    }
}
