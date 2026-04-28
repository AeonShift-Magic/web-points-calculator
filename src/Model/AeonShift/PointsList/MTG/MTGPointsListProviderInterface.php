<?php

declare(strict_types = 1);

namespace App\Model\AeonShift\PointsList\MTG;

use JsonException;
use Psr\Cache\InvalidArgumentException;

interface MTGPointsListProviderInterface
{
    /**
     * Outputs a JSON string containing all published MTG Updates and their Points Lists as JavaScript-compatible output.
     *
     * @throws InvalidArgumentException|JsonException
     */
    public function getAllPointsListsAndUpdatesAsJSONArray(): string;
}
