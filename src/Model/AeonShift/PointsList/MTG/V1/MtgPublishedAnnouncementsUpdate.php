<?php

declare(strict_types = 1);

namespace App\Model\AeonShift\PointsList\MTG\V1;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'MtgPublishedAnnouncementsUpdate',
    required: [
        'title',
        'startingAtSimplified',
        'endingAtSimplified',
        'startingAtDate',
        'endingAtDate',
        'startingAtTimestamp',
        'endingAtTimestamp',
        'pointsList',
    ],
    properties: [
        new OA\Property(property: 'title', type: 'string', example: 'Latest — Example Update'),
        new OA\Property(property: 'startingAtSimplified', type: 'string', format: 'date', example: '2026-02-01'),
        new OA\Property(property: 'endingAtSimplified', type: 'string', format: 'date', example: null, nullable: true),
        new OA\Property(property: 'startingAtDate', type: 'string', format: 'date-time', example: '2026-02-01T00:00:00Z'),
        new OA\Property(property: 'endingAtDate', type: 'string', format: 'date-time', example: '2026-03-01T00:00:00Z'),
        new OA\Property(property: 'startingAtTimestamp', type: 'integer', format: 'int64', example: 1769904000),
        new OA\Property(property: 'endingAtTimestamp', type: 'integer', format: 'int64', example: null, nullable: true),
        new OA\Property(
            property: 'pointsList',
            description: 'Merged points list payload (structure depends on the rules model).',
            type: 'object',
            example: [
                'calculatorJsFunctionPrefix' => 'MTGPointsListModelV1',
                'pvalues'                    => ['baseSingletonStandardPlay' => 100],
                'cards'                      => [
                    'Sol Ring' => ['pointsBaseSingleton' => 10, 'firstprintedyear' => 1993],
                ],
            ],
            additionalProperties: true
        ),
    ],
    type: 'object'
)]
final class MtgPublishedAnnouncementsUpdate
{
}
