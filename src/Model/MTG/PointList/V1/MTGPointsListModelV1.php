<?php

declare(strict_types = 1);

namespace App\Model\MTG\PointList\V1;

final class MTGPointsListModelV1
{
    public function processCSVString(string $csvSourceString): bool
    {
        return true;
    }

    public function validateCSVString(string $csvSourceString): bool
    {
        return true;
    }
}
