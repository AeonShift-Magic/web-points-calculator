<?php

declare(strict_types = 1);

namespace App\Model\AeonShift\PointsList;

use App\Entity\MTG\MTGPointsList;
use App\Entity\PointsListInterface;
use App\Repository\SourceItemsRepositoryInterface;

interface MTGPointsListModelInterface extends PointsListModelInterface
{
    /**
     * Could use DTOs, but arrays are just as fast.
     *
     * @param SourceItemsRepositoryInterface $entityRepository
     * @param MTGPointsList $pointsList
     *
     * @return array{
     *     cards: array<string, array{ ... }>,
     *     unranked: array<int, array{ ... }>,
     *     pvalues: array{
     *         baseSingletonStandardPlay: float|null,
     *         baseQuadruplesStandardPlay: float|null,
     *         duelCommanderStandardPlay: float|null,
     *         commanderStandardPlay: float|null,
     *         highlanderStandardPlay: float|null,
     *         modernStandardPlay: float|null,
     *         pioneerStandardPlay: float|null,
     *         standardStandardPlay: float|null,
     *         baseSingletonLitePlay: float|null,
     *         baseQuadruplesLitePlay: float|null,
     *         duelCommanderLitePlay: float|null,
     *         commanderLitePlay: float|null,
     *         highlanderLitePlay: float|null,
     *         modernLitePlay: float|null,
     *         pioneerLitePlay: float|null,
     *         standardLitePlay: float|null,
     *         baseSingletonPowerPlay: float|null,
     *         baseQuadruplesPowerPlay: float|null,
     *         duelCommanderPowerPlay: float|null,
     *         commanderPowerPlay: float|null,
     *         highlanderPowerPlay: float|null,
     *         modernPowerPlay: float|null,
     *         pioneerPowerPlay: float|null,
     *         standardPowerPlay: float|null
     *     },
     *     calculatorJsFunctionPrefix: string,
     *     timelineprecedences: array<string, int>
     * }
     */
    public function mergeMTGSourceAndPointsListAsArray(SourceItemsRepositoryInterface $entityRepository, PointsListInterface $pointsList): array;
}
