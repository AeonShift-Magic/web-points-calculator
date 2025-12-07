<?php

declare(strict_types = 1);

namespace App\Model\Source\Factory;

use App\Entity\MTG\MTGCardSourceActivityHistory;
use App\Entity\SourceActivityHistoryInterface;
use InvalidArgumentException;

/**
 * Returns the appropriate SourceActivityHistory instance based on the provided license.
 */
final class SourceActivityHistoryFactory
{
    public function create(string $license): SourceActivityHistoryInterface
    {
        // Insert future licenses here
        return match ($license) {
            'MTG'   => new MTGCardSourceActivityHistory(),
            default => throw new InvalidArgumentException("Unsupported license: $license"),
        };
    }
}
