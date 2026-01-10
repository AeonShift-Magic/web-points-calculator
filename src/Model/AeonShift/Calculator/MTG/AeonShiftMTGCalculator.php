<?php

declare(strict_types = 1);

namespace App\Model\AeonShift\Calculator\MTG;

use App\Entity\MTG\MTGPointsListCard;
use App\Entity\MTG\MTGSourceCard;
use App\Entity\PointsListInterface;
use App\Repository\SourceItemsRepositoryInterface;
use DateTimeImmutable;
use const JSON_THROW_ON_ERROR;

final class AeonShiftMTGCalculator
{
    public const string UNRANKED_CARD_NAME = '((unranked))';

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
                    ->setIsLegalDuel(true)
                    ->setIsLegalDuelSpecial(true)
                    ->setIsLegal2HG(true)
                    ->setIsLegal2HGSpecial(true)
                    ->setIsLegalMulti(true)
                    ->setIsLegalMultiSpecial(true)
                    ->setFirstPrintedAt(new DateTimeImmutable('1993-08-05'))
                    ->setFirstPrintedYear(1993)
                    ->setMaximumTimelineLegality('printed')
                    ->setManaValue(0)
                    ->setIsWhite(false)
                    ->setIsBlue(false)
                    ->setIsBlack(false)
                    ->setIsGreen(false)
                    ->setIsColorless(false)
                    ->setPointsDuel($pointListCard->getPointsDuel())
                    ->setPointsDuelSpecial($pointListCard->getPointsDuelSpecial())
                    ->setPoints2HG($pointListCard->getPoints2HG())
                    ->setPoints2HGSpecial($pointListCard->getPoints2HGSpecial())
                    ->setPointsMulti($pointListCard->getPointsMulti())
                    ->setPointsMultiSpecial($pointListCard->getPointsMultiSpecial());
            }

            /** @var MTGSourceCard $sourceCard */
            foreach ($sourceCards as $sourceCard) {
                if ($sourceCard->getNameEN() === $pointListCard->getNameEN()) {
                    $sourceCard->setPointsDuel($pointListCard->getPointsDuel());
                    $sourceCard->setPointsDuelSpecial($pointListCard->getPointsDuelSpecial());
                    $sourceCard->setPoints2HG($pointListCard->getPoints2HG());
                    $sourceCard->setPoints2HGSpecial($pointListCard->getPoints2HGSpecial());
                    $sourceCard->setPointsMulti($pointListCard->getPointsMulti());
                    $sourceCard->setPointsMultiSpecial($pointListCard->getPointsMultiSpecial());

                    continue 2;
                }
            }
        }

        return $sourceCards;
    }
}
