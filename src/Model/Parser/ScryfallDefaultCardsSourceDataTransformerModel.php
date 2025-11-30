<?php

declare(strict_types = 1);

namespace App\Model\Parser;

use function count;
use DateMalformedStringException;
use DateTimeImmutable;
use const DIRECTORY_SEPARATOR;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use function in_array;
use function is_array;
use function is_string;
use const JSON_THROW_ON_ERROR;
use JsonException;
use JsonMachine\Items;
use JsonMachine\JsonDecoder\ExtJsonDecoder;
use Monolog\Handler\StreamHandler;
use Monolog\Level;
use Monolog\Logger;
use RuntimeException;
use Throwable;

class ScryfallDefaultCardsSourceDataTransformerModel
{
    private const int BATCH_SIZE = 100;

    /**
     * @var array<string, array<string, mixed>> Cache of existing cards by oracle_id
     */
    private array $existingCardsCache = [];

    private Logger $fileLogger;

    public function __construct(
        private readonly Connection $connection,
        private readonly string $projectDir,
        private readonly string $cardsSourceDir,
    )
    {
    }

    /**
     * Parse and import cards from the Scryfall JSON file.
     *
     * @param callable|null $progressCallback Callback function: function(int $processedCount, int $insertedCount, int $updatedCount, int $skippedCount): void
     *
     * @throws Exception|RuntimeException
     *
     * @return array{processed: int, inserted: int, updated: int, skipped: int, errors: int}
     */
    public function parseAndImport(?callable $progressCallback = null): array
    {
        $jsonFilePath = $this->findJsonFile();

        // Initialize file logger
        $this->initializeLogger($jsonFilePath);

        $this->fileLogger->info(sprintf('Starting import from: %s', $jsonFilePath));

        $processedCount = 0;
        $insertedCount = 0;
        $updatedCount = 0;
        $skippedCount = 0;
        $errorCount = 0;
        $currentDate = new DateTimeImmutable();

        $batch = [];

        try {
            // Load all existing cards into memory cache for fast lookups
            $this->fileLogger->info('Loading existing cards into memory cache...');
            $this->loadExistingCardsCache();
            $this->fileLogger->info(sprintf('Loaded %d existing cards into cache', count($this->existingCardsCache)));

            // Use JsonMachine to stream the JSON file
            $cards = Items::fromFile($jsonFilePath, ['decoder' => new ExtJsonDecoder(true)]);

            foreach ($cards as $card) {
                try {
                    // Skip tokens and non-card objects
                    if (isset($card['layout']) && in_array($card['layout'], ['token', 'emblem', 'art_series'], true)) {
                        continue;
                    }

                    $transformedCard = $this->transformCard($card, $currentDate);

                    if ($transformedCard !== null) {
                        $batch[] = $transformedCard;

                        if (count($batch) >= self::BATCH_SIZE) {
                            $result = $this->processBatch($batch, $currentDate);
                            $insertedCount += $result['inserted'];
                            $updatedCount += $result['updated'];
                            $skippedCount += $result['skipped'];
                            $batch = [];
                        }
                    }

                    ++$processedCount;

                    if ($progressCallback !== null && $processedCount % 100 === 0) {
                        $progressCallback($processedCount, $insertedCount, $updatedCount, $skippedCount);
                    }
                } catch (Throwable $e) {
                    ++$errorCount;
                    $this->fileLogger->error(
                        sprintf(
                            'Error processing card: %s (Card: %s)',
                            $e->getMessage(),
                            $card['name'] ?? 'unknown'
                        )
                    );
                }
            }

            // Process remaining batch
            if (count($batch) > 0) {
                $result = $this->processBatch($batch, $currentDate);
                $insertedCount += $result['inserted'];
                $updatedCount += $result['updated'];
                $skippedCount += $result['skipped'];
            }

            // Final callback
            if ($progressCallback !== null) {
                $progressCallback($processedCount, $insertedCount, $updatedCount, $skippedCount);
            }

            $this->fileLogger->info(
                sprintf(
                    'Import completed - Processed: %d, Inserted: %d, Updated: %d, Skipped: %d, Errors: %d',
                    $processedCount,
                    $insertedCount,
                    $updatedCount,
                    $skippedCount,
                    $errorCount
                )
            );

            // Clear cache to free memory
            $this->existingCardsCache = [];
        } catch (Throwable $e) {
            $this->fileLogger->error(sprintf('Import failed: %s', $e->getMessage()));

            throw new RuntimeException('Failed to import cards: ' . $e->getMessage(), 0, $e);
        }

        return [
            'processed' => $processedCount,
            'inserted'  => $insertedCount,
            'updated'   => $updatedCount,
            'skipped'   => $skippedCount,
            'errors'    => $errorCount,
        ];
    }

    /**
     * Calculate color identity flags.
     *
     * @return array{isBlack: bool, isBlue: bool, isColorless: bool, isGreen: bool, isRed: bool, isWhite: bool}
     */
    private function calculateColorFlags(array $colorIdentity): array
    {
        return [
            'isBlack'     => in_array('B', $colorIdentity, true),
            'isBlue'      => in_array('U', $colorIdentity, true),
            'isColorless' => count($colorIdentity) === 0,
            'isGreen'     => in_array('G', $colorIdentity, true),
            'isRed'       => in_array('R', $colorIdentity, true),
            'isWhite'     => in_array('W', $colorIdentity, true),
        ];
    }

    /**
     * Calculate if a card is command zone eligible.
     */
    private function calculateCommandZoneEligibility(array $card): bool
    {
        $typeLine = $card['type_line'] ?? '';
        $oracleText = $card['oracle_text'] ?? '';
        $name = $card['name'] ?? '';

        // Special case: Grist, the Hunger Tide
        if ($name === 'Grist, the Hunger Tide') {
            return true;
        }

        // Check if oracle text contains "can be your commander"
        if (mb_stripos($oracleText, 'can be your commander') !== false) {
            return true;
        }

        // Check if type line contains Legendary AND one of the required types
        if (mb_stripos($typeLine, 'Legendary') !== false) {
            $requiredTypes = ['Creature', 'Vehicle', 'Background', 'Spacecraft'];
            foreach ($requiredTypes as $type) {
                if (mb_stripos($typeLine, $type) !== false) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Calculate legality flags based on card properties.
     *
     * @return array{isLegalDuel: bool, isLegalMulti: bool, isLegal2HG: bool}
     */
    private function calculateLegality(array $card, DateTimeImmutable $currentDate): array
    {
        $legalities = $card['legalities'] ?? [];
        $borderColor = $card['border_color'] ?? '';
        $games = $card['games'] ?? [];
        $typeLine = $card['type_line'] ?? '';
        $oracleText = $card['oracle_text'] ?? '';
        $releasedAt = $card['released_at'] ?? '';
        $securityStamp = $card['security_stamp'] ?? '';
        $name = $card['name'] ?? '';

        // Default: legal if vintage is legal or restricted
        $isLegal = isset($legalities['vintage']) &&
            in_array($legalities['vintage'], ['legal', 'restricted'], true);

        // Special case: Shahrazad is always legal
        if ($name === 'Shahrazad') {
            return [
                'isLegalDuel'  => true,
                'isLegalMulti' => true,
                'isLegal2HG'   => true,
            ];
        }

        // Check if card should be illegal
        if ($isLegal) {
            // Border color is silver
            if ($borderColor === 'silver') {
                $isLegal = false;
            }

            // Games doesn't contain "paper"
            if (! in_array('paper', $games, true)) {
                $isLegal = false;
            }

            // Type line contains forbidden types
            $forbiddenTypes = ['Conspiracy', 'Attraction', 'Sticker', 'Dungeon', 'Contraption'];
            foreach ($forbiddenTypes as $type) {
                if (mb_stripos($typeLine, $type) !== false) {
                    $isLegal = false;

                    break;
                }
            }

            // Oracle text contains forbidden phrases
            if (mb_stripos($oracleText, 'playing for ante') !== false) {
                $isLegal = false;
            }

            if (mb_stripos($oracleText, 'sticker') !== false) {
                $isLegal = false;
            }

            // Check for "open" OR "visit" AND "attraction"
            $hasOpenOrVisit = mb_stripos($oracleText, 'open') !== false || mb_stripos($oracleText, 'visit') !== false;
            $hasAttraction = mb_stripos($oracleText, 'attraction') !== false;
            if ($hasOpenOrVisit && $hasAttraction) {
                $isLegal = false;
            }

            // Released at is in the future
            if (! empty($releasedAt)) {
                try {
                    $releaseDate = new DateTimeImmutable($releasedAt);
                    if ($releaseDate > $currentDate) {
                        $isLegal = false;
                    }
                } catch (\Exception) {
                    // If date parsing fails, assume it's not legal
                    $isLegal = false;
                }
            }

            // Security stamp contains acorn or heart
            if (in_array($securityStamp, ['acorn', 'heart'], true)) {
                $isLegal = false;
            }
        }

        return [
            'isLegalDuel'  => $isLegal,
            'isLegalMulti' => $isLegal,
            'isLegal2HG'   => $isLegal,
        ];
    }

    /**
     * Calculate multi-commander zone type.
     */
    private function calculateMultiCZType(array $card, bool $isCommandZoneEligible): string
    {
        if (! $isCommandZoneEligible) {
            return '';
        }

        $oracleText = $card['oracle_text'] ?? '';
        $typeLine = $card['type_line'] ?? '';

        // Check oracle text first (more specific)
        if (mb_stripos($oracleText, "Doctor's companion") !== false) {
            return 'doctors_companion';
        }

        if (mb_stripos($oracleText, 'Choose a Background') !== false) {
            return 'choose_a_background';
        }

        if (mb_stripos($oracleText, 'Friends forever') !== false) {
            return 'friends_forever';
        }

        if (mb_stripos($oracleText, 'partner') !== false) {
            return 'partner';
        }

        // Check type line
        if (mb_stripos($typeLine, 'Time Lord Doctor') !== false) {
            return 'doctors_companion';
        }

        if (mb_stripos($typeLine, 'Background') !== false) {
            return 'choose_a_background';
        }

        return '';
    }

    /**
     * Calculate if a card is eligible for multiple command zones.
     */
    private function calculateMultipleCommandZoneEligibility(array $card, bool $isCommandZoneEligible): bool
    {
        if (! $isCommandZoneEligible) {
            return false;
        }

        $oracleText = $card['oracle_text'] ?? '';
        $typeLine = $card['type_line'] ?? '';

        $partnerKeywords = ['partner', 'Friends forever', 'Choose a Background'];
        foreach ($partnerKeywords as $keyword) {
            if (mb_stripos($oracleText, $keyword) !== false) {
                return true;
            }
        }

        $partnerTypes = ['Background', 'Time Lord Doctor'];
        foreach ($partnerTypes as $type) {
            if (mb_stripos($typeLine, $type) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Calculate special legality flags based on command zone eligibility.
     *
     * @return array{isLegal2HGSpecial: bool, isLegalDuelSpecial: bool, isLegalMultiSpecial: bool}
     */
    private function calculateSpecialLegality(
        bool $isLegal2HG,
        bool $isLegalDuel,
        bool $isLegalMulti,
        bool $isCommandZoneEligible
    ): array
    {
        return [
            'isLegal2HGSpecial'   => $isLegal2HG && $isCommandZoneEligible,
            'isLegalDuelSpecial'  => $isLegalDuel && $isCommandZoneEligible,
            'isLegalMultiSpecial' => $isLegalMulti && $isCommandZoneEligible,
        ];
    }

    /**
     * Find the JSON file in the cards source directory.
     *
     * @throws RuntimeException
     */
    private function findJsonFile(): string
    {
        $cardsSourcePath = $this->projectDir . DIRECTORY_SEPARATOR . $this->cardsSourceDir;

        if (! is_dir($cardsSourcePath)) {
            throw new RuntimeException(sprintf('Cards source directory does not exist: %s', $cardsSourcePath));
        }

        $files = glob($cardsSourcePath . DIRECTORY_SEPARATOR . '*.json');

        if ($files === false || count($files) === 0) {
            throw new RuntimeException(sprintf('No JSON file found in: %s', $cardsSourcePath));
        }

        if (count($files) > 1) {
            throw new RuntimeException(sprintf('Multiple JSON files found in: %s', $cardsSourcePath));
        }

        return $files[0];
    }

    /**
     * Format a value for logging purposes.
     *
     * @param mixed $value
     *
     * @throws JsonException
     *
     * @return string
     */
    private function formatValueForLog(mixed $value): string
    {
        if ($value === null) {
            return 'NULL';
        }

        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if (is_int($value)) {
            // Special formatting for boolean-like integers
            if ($value === 0 || $value === 1) {
                return $value === 1 ? '1 (true)' : '0 (false)';
            }

            return (string)$value;
        }

        if (is_float($value)) {
            return number_format($value, 2);
        }

        if (is_string($value)) {
            // Truncate very long strings (like JSON)
            if (mb_strlen($value) > 100) {
                return mb_substr($value, 0, 97) . '...';
            }

            return $value;
        }

        if (is_array($value)) {
            return json_encode($value, JSON_THROW_ON_ERROR);
        }

        return (string)$value;
    }

    /**
     * Initialize file logger for this import session.
     */
    private function initializeLogger(string $jsonFilePath): void
    {
        $pathInfo = pathinfo($jsonFilePath);
        $directory = $pathInfo['dirname'];

        // Delete all existing .datatransformer.log files in the directory
        $existingLogFiles = glob($directory . DIRECTORY_SEPARATOR . '*.datatransformer.log');
        if ($existingLogFiles !== false) {
            foreach ($existingLogFiles as $logFile) {
                if (is_file($logFile)) {
                    @unlink($logFile);
                }
            }
        }

        $logFileName = $pathInfo['filename'] . '.datatransformer.log';
        $logFilePath = $directory . DIRECTORY_SEPARATOR . $logFileName;

        $this->fileLogger = new Logger('scryfall_datatransformer');
        $this->fileLogger->pushHandler(new StreamHandler($logFilePath, Level::Debug));
    }

    /**
     * Load all existing cards into memory cache.
     *
     * @throws Exception
     */
    private function loadExistingCardsCache(): void
    {
        $this->existingCardsCache = [];

        $result = $this->connection->executeQuery('SELECT * FROM as_mtgsource_card');

        while ($row = $result->fetchAssociative()) {
            if (isset($row['oracle_id'])) {
                $this->existingCardsCache[$row['oracle_id']] = $row;
            }
        }
    }

    /**
     * Prepare data for update operation.
     *
     * @param array<string, mixed> $newData
     * @param array<string, mixed> $existingCard
     *
     * @throws DateMalformedStringException|JsonException
     *
     * @return array<string, mixed>
     */
    private function prepareUpdateData(array $newData, array $existingCard, DateTimeImmutable $currentDate): array
    {
        $updateData = [];

        // Check if we should update first printed date (only if new card is earlier or card is legal nowhere)
        $cardIsLegalNowhere = ! $newData['is_legal_duel'] &&
            ! $newData['is_legal_multi'] &&
            ! $newData['is_legal_2hg'];

        if (! $cardIsLegalNowhere) {
            $newDate = new DateTimeImmutable($newData['first_printed_at']);
            $existingDate = new DateTimeImmutable($existingCard['first_printed_at']);

            if ($newDate < $existingDate) {
                // Found an earlier printing - update all printing-related fields
                $updateData['first_printed_at'] = $newData['first_printed_at'];
                $updateData['first_printed_set_code'] = $newData['first_printed_set_code'];
                $updateData['first_printed_year'] = $newData['first_printed_year'];
                $updateData['scryfall_id'] = $newData['scryfall_id'];
                $updateData['scryfall_uri'] = $newData['scryfall_uri'];
            }
        }

        // Update legalities if new card is legal and existing is not
        $legalityFields = [
            'is_legal_duel'          => 'is_legal_duel',
            'is_legal_multi'         => 'is_legal_multi',
            'is_legal_2hg'           => 'is_legal_2hg',
            'is_legal_duel_special'  => 'is_legal_duel_special',
            'is_legal_multi_special' => 'is_legal_multi_special',
            'is_legal_2hg_special'   => 'is_legal_2hg_special',
        ];

        foreach ($legalityFields as $field => $dbField) {
            if ($newData[$field] && ! $existingCard[$dbField]) {
                $updateData[$dbField] = $newData[$field];
            }
        }

        // Check if these fields actually changed before updating
        // NOTE: scryfall_id and scryfall_uri are NOT included here because they're printing-specific
        // and should only be updated when first_printed_at is updated
        $fieldsToCheck = [
            'name_en',
            'mana_value',
            'color_identity',
            'is_command_zone_eligible',
            'is_multiple_command_zone_eligible',
            'multi_cz_type',
            'is_black',
            'is_blue',
            'is_colorless',
            'is_green',
            'is_red',
            'is_white',
        ];

        foreach ($fieldsToCheck as $field) {
            // Convert existing card value to match new data format for comparison
            $existingValue = $existingCard[$field] ?? null;
            $newValue = $newData[$field];

            // Special handling for different data types
            if ($field === 'mana_value') {
                $existingValue = (float)$existingValue;
            } elseif ($field === 'color_identity') {
                // Normalize JSON comparison - decode existing and compare arrays
                $existingArray = json_decode($existingValue, true, 512, JSON_THROW_ON_ERROR);
                $newArray = json_decode($newValue, true, 512, JSON_THROW_ON_ERROR);

                // Compare as arrays, not as JSON strings
                if ($existingArray === $newArray) {
                    continue; // Skip this field - no actual change
                }
            } elseif (in_array($field, ['is_command_zone_eligible', 'is_multiple_command_zone_eligible', 'is_black', 'is_blue', 'is_colorless', 'is_green',
                'is_red', 'is_white'], true)) {
                $existingValue = (int)(bool)$existingValue;
            }

            // Only add to update if value has changed
            if ($existingValue !== $newValue) {
                $updateData[$field] = $newValue;
            }
        }

        // Only add updated_at timestamp if there are actual changes
        if (count($updateData) > 0) {
            $updateData['updated_at'] = $currentDate->format('Y-m-d H:i:s');
        }

        return $updateData;
    }

    /**
     * Process a batch of cards (insert or update).
     *
     * @param array<int, array<string, mixed>> $batch
     *
     * @throws DateMalformedStringException|Exception|JsonException
     *
     * @return array{inserted: int, updated: int, skipped: int}
     */
    private function processBatch(array $batch, DateTimeImmutable $currentDate): array
    {
        $insertedCount = 0;
        $updatedCount = 0;
        $skippedCount = 0;

        foreach ($batch as $cardData) {
            $oracleId = $cardData['oracle_id'];
            $cardName = $cardData['name_en'];

            // Check cache instead of querying database
            if (! isset($this->existingCardsCache[$oracleId])) {
                // Insert new card with timestamps
                $cardData['created_at'] = $currentDate->format('Y-m-d H:i:s');
                $cardData['updated_at'] = $currentDate->format('Y-m-d H:i:s');

                $this->connection->insert('as_mtgsource_card', $cardData);

                // Add to cache for future lookups
                $this->existingCardsCache[$oracleId] = $cardData;

                $this->fileLogger->info(
                    sprintf(
                        '[INSERT] Card "%s" (oracle_id: %s) inserted into database',
                        $cardName,
                        $oracleId
                    ),
                    [
                        'scryfall_id' => $cardData['scryfall_id'],
                        'set'         => $cardData['first_printed_set_code'],
                    ]
                );

                ++$insertedCount;
            } else {
                // Update existing card
                $existingCard = $this->existingCardsCache[$oracleId];
                $updateData = $this->prepareUpdateData($cardData, $existingCard, $currentDate);

                if (count($updateData) > 0) {
                    // Build list of changed fields for logging
                    $changedFields = [];
                    foreach ($updateData as $field => $newValue) {
                        if ($field === 'updated_at') {
                            continue; // Don't log timestamp changes
                        }

                        $oldValue = $existingCard[$field] ?? null;

                        // Format values for logging
                        $oldValueFormatted = $this->formatValueForLog($oldValue);
                        $newValueFormatted = $this->formatValueForLog($newValue);

                        $changedFields[] = sprintf(
                            '%s: [%s] → [%s]',
                            $field,
                            $oldValueFormatted,
                            $newValueFormatted
                        );
                    }

                    $this->connection->update(
                        'as_mtgsource_card',
                        $updateData,
                        ['oracle_id' => $oracleId]
                    );

                    // Update cache with new data
                    $this->existingCardsCache[$oracleId] = array_merge($existingCard, $updateData);

                    $this->fileLogger->info(
                        sprintf(
                            '[UPDATE] Card "%s" (oracle_id: %s) updated. Changes: %s',
                            $cardName,
                            $oracleId,
                            implode(', ', $changedFields)
                        ),
                        [
                            'field_count' => count($changedFields),
                        ]
                    );

                    ++$updatedCount;
                } else {
                    // No updates needed - card skipped
                    $this->fileLogger->debug(
                        sprintf(
                            '[SKIP] Card "%s" (oracle_id: %s) - no changes detected',
                            $cardName,
                            $oracleId
                        )
                    );

                    ++$skippedCount;
                }
            }
        }

        return [
            'inserted' => $insertedCount,
            'updated'  => $updatedCount,
            'skipped'  => $skippedCount,
        ];
    }

    /**
     * Transform a Scryfall card to database format.
     *
     * @param array<string, mixed> $card
     *
     * @throws JsonException
     *
     * @return array<string, mixed>|null
     */
    private function transformCard(array $card, DateTimeImmutable $currentDate): ?array
    {
        // Skip cards without oracle_id
        if (! isset($card['oracle_id']) || ! is_string($card['oracle_id'])) {
            return null;
        }

        // Extract basic fields
        $oracleId = $card['oracle_id'];
        $scryfallId = $card['id'] ?? null;
        $name = $card['name'] ?? '';
        $manaValue = (float)($card['cmc'] ?? 0.0);
        $colorIdentity = $card['color_identity'] ?? [];
        $scryfallUri = $card['scryfall_uri'] ?? '';
        $set = $card['set'] ?? '';
        $releasedAt = $card['released_at'] ?? '';

        // Ensure color identity is an array
        if (! is_array($colorIdentity)) {
            $colorIdentity = [];
        }

        // Calculate legalities
        $legality = $this->calculateLegality($card, $currentDate);
        $isCommandZoneEligible = $this->calculateCommandZoneEligibility($card);

        // Calculate special legalities
        $specialLegality = $this->calculateSpecialLegality(
            $legality['isLegal2HG'],
            $legality['isLegalDuel'],
            $legality['isLegalMulti'],
            $isCommandZoneEligible
        );

        // Calculate command zone flags
        $isMultipleCommandZoneEligibility = $this->calculateMultipleCommandZoneEligibility($card, $isCommandZoneEligible);
        $multiCZType = $this->calculateMultiCZType($card, $isCommandZoneEligible);

        // Calculate color flags
        $colorFlags = $this->calculateColorFlags($colorIdentity);

        // Parse release date
        $firstPrintedAt = null;
        $firstPrintedYear = 0;

        try {
            if (! empty($releasedAt)) {
                $date = new DateTimeImmutable($releasedAt);
                $firstPrintedAt = $date->format('Y-m-d');
                $firstPrintedYear = (int)$date->format('Y');
            }
        } catch (\Exception) {
            $firstPrintedAt = $currentDate->format('Y-m-d');
            $firstPrintedYear = (int)$currentDate->format('Y');
        }

        return [
            'oracle_id'                         => $oracleId,
            'scryfall_id'                       => $scryfallId,
            'name_en'                           => $name,
            'mana_value'                        => $manaValue,
            'color_identity'                    => json_encode($colorIdentity, JSON_THROW_ON_ERROR),
            'scryfall_uri'                      => $scryfallUri,
            'first_printed_at'                  => $firstPrintedAt,
            'first_printed_set_code'            => $set,
            'first_printed_year'                => $firstPrintedYear,
            'is_legal_duel'                     => (int)$legality['isLegalDuel'],
            'is_legal_multi'                    => (int)$legality['isLegalMulti'],
            'is_legal_2hg'                      => (int)$legality['isLegal2HG'],
            'is_legal_duel_special'             => (int)$specialLegality['isLegalDuelSpecial'],
            'is_legal_multi_special'            => (int)$specialLegality['isLegalMultiSpecial'],
            'is_legal_2hg_special'              => (int)$specialLegality['isLegal2HGSpecial'],
            'is_command_zone_eligible'          => (int)$isCommandZoneEligible,
            'is_multiple_command_zone_eligible' => (int)$isMultipleCommandZoneEligibility,
            'multi_cz_type'                     => $multiCZType,
            'points_duel'                       => 0.0,
            'points_multi'                      => 0.0,
            'points_2hg'                        => 0.0,
            'points_duel_special'               => 0.0,
            'points_multi_special'              => 0.0,
            'points_2hg_special'                => 0.0,
            'is_black'                          => (int)$colorFlags['isBlack'],
            'is_blue'                           => (int)$colorFlags['isBlue'],
            'is_colorless'                      => (int)$colorFlags['isColorless'],
            'is_green'                          => (int)$colorFlags['isGreen'],
            'is_red'                            => (int)$colorFlags['isRed'],
            'is_white'                          => (int)$colorFlags['isWhite'],
        ];
    }
}
