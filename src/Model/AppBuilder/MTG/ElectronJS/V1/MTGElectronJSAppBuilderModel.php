<?php

declare(strict_types = 1);

namespace App\Model\AppBuilder\MTG\ElectronJS\V1;

use App\Model\Source\Factory\SourceActivityHistoryFactory;

/**
 * This model transforms Scryfall JSON data from their "Default Cards" set (all versions only in English,
 * or native name if none exist in English) into the format used by the MTG Source importer.
 *
 * Uses a Lock to prevent concurrent execution.
 * Uses native arrays instead of DTOs.
 */
final class MTGElectronJSAppBuilderModel
{
    /** @var string License identifier for MTG sources */
    private const string LICENSE = 'MTG';

    /** @var int The overall version of this model, for history */
    private const int VERSION = 1;

    public function __construct(
        SourceActivityHistoryFactory $sourceActivityHistoryFactory,
    )
    {
    }
}
