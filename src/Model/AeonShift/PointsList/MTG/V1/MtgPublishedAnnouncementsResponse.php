<?php

declare(strict_types = 1);

namespace App\Model\AeonShift\PointsList\MTG\V1;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'MtgPublishedAnnouncementsResponse',
    required: ['updates'],
    properties: [
        new OA\Property(
            property: 'updates',
            description: 'Map of update id -> update payload.',
            type: 'object',
            example: [
                '2' => [
                    'title'                => 'January 2026 Announcement (Current List)',
                    'startingAtSimplified' => '2026-01-26',
                    'endingAtSimplified'   => null,
                    'startingAtDate'       => '2026-01-26T20:00:00Z',
                    'endingAtDate'         => '2026-03-30T19:59:00Z',
                    'startingAtTimestamp'  => 1769454000,
                    'endingAtTimestamp'    => null,
                    'pointsList'           => [
                        'calculatorJsFunctionPrefix' => 'MTGPointsListModelV1',
                        'timelineprecedences'        => ['printed' => 10],
                        'pvalues'                    => ['baseSingletonStandardPlay' => 100],
                        'unranked'                   => ['mv' => 0],
                        'cards'                      => [
                            'Some Card' => ['mv' => 2, 'timeline' => 'funny'],
                        ],
                    ],
                ],
            ],
            additionalProperties: new OA\AdditionalProperties(
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
                    new OA\Property(property: 'title', type: 'string', example: 'January 2026 Announcement (Current List)'),
                    new OA\Property(property: 'startingAtSimplified', type: 'string', format: 'date', example: '2026-01-26'),
                    new OA\Property(property: 'endingAtSimplified', type: 'string', format: 'date', example: null, nullable: true),
                    new OA\Property(property: 'startingAtDate', type: 'string', format: 'date-time', example: '2026-01-26T20:00:00Z'),
                    new OA\Property(property: 'endingAtDate', type: 'string', format: 'date-time', example: '2026-03-30T19:59:00Z'),
                    new OA\Property(property: 'startingAtTimestamp', type: 'integer', format: 'int64', example: 1769454000),
                    new OA\Property(property: 'endingAtTimestamp', type: 'integer', format: 'int64', example: null, nullable: true),

                    new OA\Property(
                        property: 'pointsList',
                        required: [
                            'cards',
                            'unranked',
                            'timelineprecedences',
                            'calculatorJsFunctionPrefix',
                            'pvalues',
                        ],
                        properties: [
                            new OA\Property(
                                property: 'calculatorJsFunctionPrefix',
                                description: 'Prefix for the JS calculator functions to use for this points list.',
                                type: 'string',
                                example: 'MTGPointsListModelV1'
                            ),
                            new OA\Property(
                                property: 'timelineprecedences',
                                description: 'Timeline precedence values used by the rules model.',
                                type: 'object',
                                example: [
                                    'unranked' => -1,
                                    'printed'  => 10,
                                    'funny'    => 20,
                                    'eternal'  => 30,
                                    'modern'   => 40,
                                    'pioneer'  => 50,
                                    'standard' => 60,
                                ],
                                additionalProperties: new OA\AdditionalProperties(type: 'integer', example: 10)
                            ),
                            new OA\Property(
                                property: 'pvalues',
                                description: 'P-Values table for each format.',
                                properties: [
                                    new OA\Property(property: 'baseSingletonStandardPlay', type: 'integer', example: 100),
                                    new OA\Property(property: 'baseQuadruplesStandardPlay', type: 'integer', example: 100),
                                    new OA\Property(property: 'duelCommanderStandardPlay', type: 'integer', example: 100),
                                    new OA\Property(property: 'commanderStandardPlay', type: 'integer', example: 100),
                                    new OA\Property(property: 'highlanderStandardPlay', type: 'integer', example: 100),
                                    new OA\Property(property: 'modernStandardPlay', type: 'integer', example: 100),
                                    new OA\Property(property: 'pioneerStandardPlay', type: 'integer', example: 100),
                                    new OA\Property(property: 'standardStandardPlay', type: 'integer', example: 100),

                                    new OA\Property(property: 'baseSingletonLitePlay', type: 'integer', example: 30),
                                    new OA\Property(property: 'baseQuadruplesLitePlay', type: 'integer', example: 30),
                                    new OA\Property(property: 'duelCommanderLitePlay', type: 'integer', example: 30),
                                    new OA\Property(property: 'commanderLitePlay', type: 'integer', example: 30),
                                    new OA\Property(property: 'highlanderLitePlay', type: 'integer', example: 30),
                                    new OA\Property(property: 'modernLitePlay', type: 'integer', example: 30),
                                    new OA\Property(property: 'pioneerLitePlay', type: 'integer', example: 30),
                                    new OA\Property(property: 'standardLitePlay', type: 'integer', example: 30),

                                    new OA\Property(property: 'baseSingletonPowerPlay', type: 'integer', example: 200),
                                    new OA\Property(property: 'baseQuadruplesPowerPlay', type: 'integer', example: 200),
                                    new OA\Property(property: 'duelCommanderPowerPlay', type: 'integer', example: 200),
                                    new OA\Property(property: 'commanderPowerPlay', type: 'integer', example: 200),
                                    new OA\Property(property: 'highlanderPowerPlay', type: 'integer', example: 200),
                                    new OA\Property(property: 'modernPowerPlay', type: 'integer', example: 200),
                                    new OA\Property(property: 'pioneerPowerPlay', type: 'integer', example: 200),
                                    new OA\Property(property: 'standardPowerPlay', type: 'integer', example: 200),
                                ],
                                type: 'object',
                                additionalProperties: true
                            ),

                            new OA\Property(
                                property: 'cards',
                                description: 'Map of card name -> card data.',
                                type: 'object',
                                additionalProperties: new OA\AdditionalProperties(
                                    properties: [
                                        new OA\Property(property: 'flavorofnameen', type: 'string', example: '', nullable: true),
                                        new OA\Property(property: 'alternatenameen', type: 'string', example: '', nullable: true),
                                        new OA\Property(property: 'imageurl', type: 'string', example: 'https://cards.scryfall.io/normal/front/...jpg', nullable: true),
                                        new OA\Property(property: 'mv', type: 'number', example: 2),
                                        new OA\Property(property: 'multicztype', type: 'string', example: '', nullable: true),
                                        new OA\Property(property: 'ci', type: 'array', items: new OA\Items(type: 'string'), example: ['U']),
                                        new OA\Property(property: 'timeline', type: 'string', example: 'funny'),
                                        new OA\Property(property: 'mvalue', type: 'number', example: 0.38),
                                        new OA\Property(property: 'tix', type: 'number', example: 0),
                                        new OA\Property(property: 'firstprintedyear', type: 'integer', example: 2004),
                                        new OA\Property(property: 'firstprintedon', type: 'integer', format: 'int64', example: 1100818800),

                                        new OA\Property(property: 'legal2HG', type: 'boolean', example: false),
                                        new OA\Property(property: 'legal2HGSpecial', type: 'boolean', example: false),
                                        new OA\Property(property: 'legalDC', type: 'boolean', example: false),
                                        new OA\Property(property: 'legalDCSpecial', type: 'boolean', example: false),
                                        new OA\Property(property: 'legalCEDH', type: 'boolean', example: false),
                                        new OA\Property(property: 'legalCEDHSpecial', type: 'boolean', example: false),

                                        new OA\Property(property: 'czeligible', type: 'boolean', example: false),
                                        new OA\Property(property: 'multiczeligible', type: 'boolean', example: false),
                                        new OA\Property(property: 'maxcopies', type: 'integer', example: 1, nullable: true),

                                        new OA\Property(property: 'b', type: 'boolean', example: false),
                                        new OA\Property(property: 'u', type: 'boolean', example: true),
                                        new OA\Property(property: 'r', type: 'boolean', example: false),
                                        new OA\Property(property: 'g', type: 'boolean', example: false),
                                        new OA\Property(property: 'w', type: 'boolean', example: false),
                                        new OA\Property(property: 'c', type: 'boolean', example: false),

                                        // Points fields may or may not exist on regular cards depending on your merge logic;
                                        // keep them optional but documented.
                                        new OA\Property(property: 'pointsBaseSingleton', type: 'integer', example: 10, nullable: true),
                                        new OA\Property(property: 'pointsBaseQuadruples', type: 'integer', example: 10, nullable: true),
                                        new OA\Property(property: 'pointsDuelCommander', type: 'integer', example: 10, nullable: true),
                                        new OA\Property(property: 'pointsDuelCommanderSpecial', type: 'integer', example: 40, nullable: true),
                                        new OA\Property(property: 'pointsCommander', type: 'integer', example: 10, nullable: true),
                                        new OA\Property(property: 'pointsCommanderSpecial', type: 'integer', example: 30, nullable: true),
                                        new OA\Property(property: 'pointsHighlander', type: 'integer', example: 10, nullable: true),
                                        new OA\Property(property: 'pointsModern', type: 'integer', example: 10, nullable: true),
                                        new OA\Property(property: 'pointsPioneer', type: 'integer', example: 10, nullable: true),
                                        new OA\Property(property: 'pointsStandard', type: 'integer', example: 10, nullable: true),
                                    ],
                                    type: 'object',
                                    additionalProperties: true
                                )
                            ),

                            new OA\Property(
                                property: 'unranked',
                                description: 'Fallback card definition used when a card is not found in the points list.',
                                properties: [
                                    new OA\Property(property: 'flavorofnameen', type: 'string', example: null, nullable: true),
                                    new OA\Property(property: 'alternatenameen', type: 'string', example: '', nullable: true),
                                    new OA\Property(property: 'imageurl', type: 'string', example: '', nullable: true),
                                    new OA\Property(property: 'mv', type: 'number', example: 0),
                                    new OA\Property(property: 'multicztype', type: 'string', example: '', nullable: true),
                                    new OA\Property(property: 'ci', type: 'array', items: new OA\Items(type: 'string'), example: []),
                                    new OA\Property(property: 'timeline', type: 'string', example: 'printed'),
                                    new OA\Property(property: 'mvalue', type: 'number', example: 0),
                                    new OA\Property(property: 'tix', type: 'number', example: 0),
                                    new OA\Property(property: 'firstprintedyear', type: 'integer', example: 10000),
                                    new OA\Property(property: 'firstprintedon', type: 'integer', format: 'int64', example: 10000000000000),

                                    new OA\Property(property: 'legal2HG', type: 'boolean', example: true),
                                    new OA\Property(property: 'legal2HGSpecial', type: 'boolean', example: true),
                                    new OA\Property(property: 'legalDC', type: 'boolean', example: true),
                                    new OA\Property(property: 'legalDCSpecial', type: 'boolean', example: true),
                                    new OA\Property(property: 'legalCEDH', type: 'boolean', example: true),
                                    new OA\Property(property: 'legalCEDHSpecial', type: 'boolean', example: true),

                                    new OA\Property(property: 'czeligible', type: 'boolean', example: true),
                                    new OA\Property(property: 'multiczeligible', type: 'boolean', example: true),

                                    new OA\Property(property: 'b', type: 'boolean', example: false),
                                    new OA\Property(property: 'u', type: 'boolean', example: false),
                                    new OA\Property(property: 'r', type: 'boolean', example: false),
                                    new OA\Property(property: 'g', type: 'boolean', example: false),
                                    new OA\Property(property: 'w', type: 'boolean', example: false),
                                    new OA\Property(property: 'c', type: 'boolean', example: false),

                                    new OA\Property(property: 'pointsBaseSingleton', type: 'integer', example: 10),
                                    new OA\Property(property: 'pointsBaseQuadruples', type: 'integer', example: 10),
                                    new OA\Property(property: 'pointsDuelCommander', type: 'integer', example: 10),
                                    new OA\Property(property: 'pointsDuelCommanderSpecial', type: 'integer', example: 40),
                                    new OA\Property(property: 'pointsCommander', type: 'integer', example: 10),
                                    new OA\Property(property: 'pointsCommanderSpecial', type: 'integer', example: 30),
                                    new OA\Property(property: 'pointsHighlander', type: 'integer', example: 10),
                                    new OA\Property(property: 'pointsModern', type: 'integer', example: 10),
                                    new OA\Property(property: 'pointsPioneer', type: 'integer', example: 10),
                                    new OA\Property(property: 'pointsStandard', type: 'integer', example: 10),
                                ],
                                type: 'object',
                                additionalProperties: true
                            ),
                        ],
                        type: 'object',
                        additionalProperties: true
                    ),
                ],
                type: 'object'
            )
        ),
    ],
    type: 'object'
)]
final class MtgPublishedAnnouncementsResponse
{
}
