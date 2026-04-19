<?php

declare(strict_types = 1);

namespace App\Model\AeonShift\PointsList\MTG\V1;

use App\Entity\MTG\MTGPointsList;
use App\Entity\MTG\MTGPointsListMValue;
use App\Entity\MTG\MTGSourceCard;

final class MTGSourceCardToPointsListMValueTransformer
{
    public static function fromMTGSourceCard(MTGSourceCard $sourceCard, MTGPointsList $pointsList): MTGPointsListMValue
    {
        $MTGPointsListMValue = new MTGPointsListMValue();
        $MTGPointsListMValue->setNameEN($sourceCard->getNameEN());
        $MTGPointsListMValue->setMTGOPrice($sourceCard->getMTGOPrice());
        $MTGPointsListMValue->setMValueTrend($sourceCard->getMValueTrend());
        $MTGPointsListMValue->setPointsList($pointsList);
        $MTGPointsListMValue->setValuePoints(0.0);

        return $MTGPointsListMValue;
    }
}
