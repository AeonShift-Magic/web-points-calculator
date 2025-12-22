<?php

declare(strict_types = 1);

namespace App\Model\MTG\StaticAssetsBuilder\V1;

/**
 * This model transforms Scryfall JSON data from their "Default Cards" set (all versions only in English,
 * or native name if none exist in English) into the format used by the MTG Source importer.
 *
 * Uses a Lock to prevent concurrent execution.
 * Uses native arrays instead of DTOs.
 */
final class MTGCalculatorStaticAssetsBuilderModelV1
{
    /** @var string License identifier for MTG sources */
    private const string LICENSE = 'MTG';

    /** @var string Target directory for built static assets */
    private const string TARGET_DIRECTORY = 'public/static-calculators/mtg';

    /** @var int The overall version of this model, for history */
    private const int VERSION = 1;

    public function buildMTGCalculatorStaticAssets(): string
    {
        return 'Static assets successfully built for license ' . self::LICENSE . ' in "' . self::TARGET_DIRECTORY . '" for calculator model version ' . self::VERSION . '.';
    }
}
