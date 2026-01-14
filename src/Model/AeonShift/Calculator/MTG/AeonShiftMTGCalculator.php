<?php

declare(strict_types = 1);

namespace App\Model\AeonShift\Calculator\MTG;

use App\Entity\MTG\MTGPointsListCard;
use App\Entity\MTG\MTGSourceCard;
use App\Entity\PointsListInterface;
use App\Repository\SourceItemsRepositoryInterface;
use DateTimeImmutable;
use const JSON_THROW_ON_ERROR;
use JsonException;

final class AeonShiftMTGCalculator
{
    public const string UNRANKED_CARD_NAME = '((unranked))';

    /**
     * @param SourceItemsRepositoryInterface $entityRepository should be your Source repository
     * @param PointsListInterface $pointsList should be your Points List entity
     *
     * @throws JsonException
     *
     * @return string
     */
    public function getPointsListAsJSONArray(SourceItemsRepositoryInterface $entityRepository, PointsListInterface $pointsList): string
    {
        $mergedCards = $this->mergeMTGSourceAndPointsList($entityRepository, $pointsList);

        return json_encode($mergedCards, JSON_THROW_ON_ERROR);
    }

    /**
     * @param SourceItemsRepositoryInterface $entityRepository
     * @param PointsListInterface $pointsList
     *
     * @return array<int, MTGSourceCard>
     */
    public function mergeMTGSourceAndPointsList(SourceItemsRepositoryInterface $entityRepository, PointsListInterface $pointsList): array
    {
        /** @var array<int, MTGSourceCard> $sourceCards */
        $sourceCards = $entityRepository->getAllItemsAsArray();
        $pointListCards = $pointsList->getItems();

        /** @var MTGPointsListCard $pointListCard */
        foreach ($pointListCards as $pointListCard) {

            if (mb_strtolower($pointListCard->getNameEN()) === self::UNRANKED_CARD_NAME) {
                $sourceCards[] = new MTGSourceCard()
                    ->setNameEN($pointListCard->getNameEN())
                    ->setIsLegalDuelCommander(true)
                    ->setIsLegalDuelCommanderSpecial(true)
                    ->setIsLegal2HG(true)
                    ->setIsLegal2HGSpecial(true)
                    ->setIsLegalCommander(true)
                    ->setIsLegalCommanderSpecial(true)
                    ->setFirstPrintedAt(new DateTimeImmutable('1993-08-05'))
                    ->setFirstPrintedYear(1993)
                    ->setMaximumTimelineLegality('printed')
                    ->setManaValue(0)
                    ->setIsWhite(false)
                    ->setIsBlue(false)
                    ->setIsBlack(false)
                    ->setIsGreen(false)
                    ->setIsColorless(false)
                    ->setPointsDuelCommander($pointListCard->getPointsDuelCommander())
                    ->setPointsDuelCommanderSpecial($pointListCard->getPointsDuelCommanderSpecial())
                    ->setPoints2HG($pointListCard->getPoints2HG())
                    ->setPoints2HGSpecial($pointListCard->getPoints2HGSpecial())
                    ->setPointsCommander($pointListCard->getPointsCommander())
                    ->setPointsCommanderSpecial($pointListCard->getPointsCommanderSpecial());
            }

            /** @var MTGSourceCard $sourceCard */
            foreach ($sourceCards as $sourceCard) {
                if ($sourceCard->getNameEN() === $pointListCard->getNameEN()) {
                    $sourceCard->setPointsDuelCommander($pointListCard->getPointsDuelCommander());
                    $sourceCard->setPointsDuelCommanderSpecial($pointListCard->getPointsDuelCommanderSpecial());
                    $sourceCard->setPoints2HG($pointListCard->getPoints2HG());
                    $sourceCard->setPoints2HGSpecial($pointListCard->getPoints2HGSpecial());
                    $sourceCard->setPointsCommander($pointListCard->getPointsCommander());
                    $sourceCard->setPointsCommanderSpecial($pointListCard->getPointsCommanderSpecial());

                    continue 2;
                }
            }
        }

        return $sourceCards;
    }
}
