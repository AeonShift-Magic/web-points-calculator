<?php

declare(strict_types = 1);

namespace App\Model\MTG\Source\DataTransformer\Scryfall\V1;

use App\Entity\MTG\MTGSourceCard;
use App\Entity\SourceActivityHistoryInterface;
use App\Model\AeonShift\PointsList\MTG\V1\MTGPointsListModelV1;
use App\Model\MTG\Source\Factory\SourceActivityHistoryFactory;
use function count;
use const CURLOPT_FOLLOWLOCATION;
use const CURLOPT_HTTPHEADER;
use const CURLOPT_MAXREDIRS;
use const CURLOPT_RETURNTRANSFER;
use const CURLOPT_TIMEOUT;
use const CURLOPT_USERAGENT;
use DateMalformedStringException;
use DateTime;
use DateTimeImmutable;
use const DIRECTORY_SEPARATOR;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Doctrine\ORM\EntityManagerInterface;
use const ENT_QUOTES;
use const ENT_SUBSTITUTE;
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
use Stringable;
use Symfony\Component\Lock\Exception\LockAcquiringException;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\SharedLockInterface;
use Throwable;

/**
 * This model transforms Scryfall JSON data from their "Default Cards" set (all versions only in English,
 * or native name if none exist in English) into the format used by the MTG Source importer.
 *
 * Uses a Lock to prevent concurrent execution.
 * Uses native arrays instead of DTOs.
 */
final class MTGScryfallDefaultCardsSourceDataTransformerModelV1
{
    /** @var int How many items/cards to check before securing a DB upsert */
    private const int BATCH_SIZE = 100;

    /** @var string Channel string for DB logging */
    private const string CHANNEL = 'scryfall/defaultmtgcards/dbupdate/v' . self::VERSION;

    /** @var string License identifier for MTG sources */
    private const string LICENSE = 'MTG';

    /** @var string Lock key for preventing concurrent execution */
    private const string LOCK_KEY = 'scryfall_default_cards_processing';

    /** @var int Lock TTL in seconds (15 minutes) */
    private const int LOCK_TTL = 900;

    /** @var int The overall version of this model, for history */
    private const int VERSION = 1;

    /**
     * @var array<string, array<string, mixed>> Cache of existing cards by oracle_id
     */
    private array $existingCardsCache = [];

    private ?Logger $fileLogger = null;

    private SourceActivityHistoryInterface $sourceActivityHistory;

    public function __construct(
        private readonly Connection $connection,
        private readonly EntityManagerInterface $entityManager,
        private readonly string $projectDir,
        private readonly string $cardsSourceDir,
        private readonly string $tablePrefix,
        SourceActivityHistoryFactory $sourceActivityHistoryFactory,
        private readonly LockFactory $lockFactory,
    )
    {
        $this->sourceActivityHistory = $sourceActivityHistoryFactory->create(self::LICENSE);
    }

    /**
     * @return SharedLockInterface
     */
    public function getLock(): SharedLockInterface
    {
        // Acquire lock to prevent concurrent execution
        $lock = $this->lockFactory->createLock(self::LOCK_KEY, self::LOCK_TTL);

        if (! $lock->acquire()) {
            throw new LockAcquiringException('Another Scryfall default cards process is already running. Please wait for it to complete or try again later.');
        }

        return $lock;
    }

    /**
     * Parse and import cards from the Scryfall Default Cards JSON file.
     *
     * @param callable|null $progressCallback Callback function: function(int $processedCount, int $insertedCount, int $updatedCount, int $skippedCount): void
     * @param string $startedFrom Source identifier for where the import was started from (e.g., 'cli', 'web'), @see AbstractSourceActivityHistory
     *
     * @throws Exception|RuntimeException
     *
     * @return array{processed: int, inserted: int, updated: int, skipped: int, prices: int, errors: int, duel_ranks: int}
     */
    public function parseAndImport(?callable $progressCallback = null, string $startedFrom = 'cli'): array
    {
        ini_set('max_execution_time', 10000);

        $lock = $this->getLock();

        $jsonFilePath = $this->getLatestScryfallSourceJsonFile();

        // Initialize file fileLogger at the same directory that the JSON file is located in, erase existing log files
        $logFileName = $this->initializeLogger($jsonFilePath);

        try {
            $processedCardsCount = 0;
            $insertedCardsCount = 0;
            $updatedCardsCount = 0;
            $skippedCardsCount = 0;
            $pricesCount = 0;
            $errorCount = 0;
            $updatedMTGTop8RanksCount = 0;
            $startTime = new DateTimeImmutable();

            $this->getLogger()->info(sprintf('Starting import from: %s - V' . self::VERSION . ', setting up database trace as well', $jsonFilePath));

            // Initialize the table entry for this source activity history
            $this->initializeDatabaseSourceActivityHistory($startTime, $startedFrom, $logFileName);

            // RESET STEP: Reset maximum_timeline_legality for all cards to 'printed' before processing
            $this->getLogger()->info('Resetting maximum_timeline_legality for all cards to "printed"...');
            $this->resetAllCardsMaximumTimelineLegality();
            $this->getLogger()->info('Maximum timeline legality reset completed');

            /** @var array<int, array<string, mixed>> $batchSliceOfCards an array containing the current BATCH_SIZE cards to be processed */
            $batchSliceOfCards = [];

            /**
             * Temporary array: lowest avg price (50/50 USD/EUR) per card name (Scryfall "name").
             *
             * @var array<string, array{tix: numeric-string, usd: numeric-string, eur: numeric-string, avg: numeric-string}> $lowestAveragePricesByName
             */
            $lowestAveragePricesByName = [];

            $this->getLogger()->debug('[PRICES] Start collecting lowest average (50/50 USD/EUR) price per card name from JSON...');

            try {
                // Load all existing cards into memory cache for fast lookups
                $this->getLogger()->info('Loading existing cards into memory cache...');
                $this->loadExistingCardsCache();
                $this->getLogger()->info(sprintf('Loaded %d existing cards into cache', count($this->existingCardsCache)));

                // Use JsonMachine to stream the JSON file
                /** @var iterable<array<string, mixed>> $cards */
                $cards = Items::fromFile($jsonFilePath, ['decoder' => new ExtJsonDecoder(true)]);

                foreach ($cards as $card) {
                    try {
                        // Skip tokens and non-card objects
                        if (isset($card['layout']) && in_array($card['layout'], ['token', 'emblem', 'art_series'], true)) {
                            continue;
                        }

                        $this->collectLowestAveragePriceByNameFromRawScryfallCard($lowestAveragePricesByName, $card);

                        $transformedCard = $this->mapScryfallSourceCardDataToArray($card, $startTime);

                        if ($transformedCard !== null) {
                            $batchSliceOfCards[] = $transformedCard;

                            if (count($batchSliceOfCards) >= self::BATCH_SIZE) {
                                $result = $this->processCardsUpdateBatchSlice($batchSliceOfCards, $startTime);
                                $insertedCardsCount += $result['inserted'];
                                $updatedCardsCount += $result['updated'];
                                $skippedCardsCount += $result['skipped'];
                                $pricesCount += $result['prices'];
                                $errorCount += $result['errors'];
                                $updatedMTGTop8RanksCount += $result['duel_ranks'];
                                $batchSliceOfCards = [];
                            }
                        }

                        ++$processedCardsCount;

                        if ($progressCallback !== null && $processedCardsCount % 100 === 0) {
                            $progressCallback($processedCardsCount, $insertedCardsCount, $updatedCardsCount, $skippedCardsCount, $pricesCount, $errorCount, $updatedMTGTop8RanksCount);
                        }
                    } catch (Throwable $e) {
                        ++$errorCount;
                        $this->getLogger()->error(
                            sprintf(
                                'Error processing card: %s (Card: %s)',
                                $e->getMessage(),
                                isset($card['name']) && is_string($card['name']) ? $card['name'] : ''
                            )
                        );
                    }
                }

                $this->getLogger()->debug(sprintf(
                    '[PRICES] Finished collecting lowest average prices per name. Names: %d',
                    count($lowestAveragePricesByName)
                ));

                // Process remaining batch if any left below BATCH_SIZE total
                if (count($batchSliceOfCards) > 0) {
                    $result = $this->processCardsUpdateBatchSlice($batchSliceOfCards, $startTime);
                    $insertedCardsCount += $result['inserted'];
                    $updatedCardsCount += $result['updated'];
                    $skippedCardsCount += $result['skipped'];
                    $pricesCount += $result['prices'];
                    $errorCount += $result['errors'];
                    $updatedMTGTop8RanksCount += $result['duel_ranks'];
                }

                // Now that all cards are updated/inserted in DB, apply prices in batches.
                $updatedPricesCount = $this->applyLowestAveragePricesToCards(
                    $lowestAveragePricesByName,
                    $progressCallback,
                    $processedCardsCount,
                    $insertedCardsCount,
                    $updatedCardsCount,
                    $skippedCardsCount,
                    $pricesCount,
                    $errorCount,
                    $updatedMTGTop8RanksCount
                );

                // Update MTGTop8 cEDH popularity ranks (duel_rank)
                $updatedMTGTop8RanksCount = $this->updateMTGTop8RanksFromCedhMetaPopularity();

                // Final callback
                if ($progressCallback !== null) {
                    $progressCallback($processedCardsCount, $insertedCardsCount, $updatedCardsCount, $skippedCardsCount, $pricesCount, $errorCount, $updatedMTGTop8RanksCount);
                }

                // Finalize source activity history in both logs and DB
                $this->sourceActivityHistory->setEndedAt(new DateTime());
                $this->sourceActivityHistory->setSuccessSummary(sprintf(
                    'Import completed - Processed: %d, Inserted: %d, Updated: %d, Skipped: %d, Errors: %d, Prices updated: %d, MTGTop8 ranks updated: %d',
                    $processedCardsCount,
                    $insertedCardsCount,
                    $updatedCardsCount,
                    $skippedCardsCount,
                    $errorCount,
                    $updatedPricesCount,
                    $updatedMTGTop8RanksCount
                ));
                $this->entityManager->persist($this->sourceActivityHistory);
                $this->entityManager->flush();
                $this->getLogger()->info(
                    sprintf(
                        'Import completed - Processed: %d, Inserted: %d, Updated: %d, Skipped: %d, Errors: %d, Prices updated: %d, MTGTop8 ranks updated: %d',
                        $processedCardsCount,
                        $insertedCardsCount,
                        $updatedCardsCount,
                        $skippedCardsCount,
                        $errorCount,
                        $updatedPricesCount,
                        $updatedMTGTop8RanksCount
                    )
                );

                // Clear cache to free memory
                $this->existingCardsCache = [];
            } catch (Throwable $e) {
                // Finalize source activity history in both logs and DB
                if ($this->entityManager->isOpen()) {
                    $this->sourceActivityHistory->setEndedAt(new DateTime());
                    $this->sourceActivityHistory->setErrorSummary(sprintf('Import failed: %s', $e->getMessage()));
                    $this->entityManager->persist($this->sourceActivityHistory);
                    $this->entityManager->flush();
                }

                if ($this->getLogger() !== null) {
                    $this->getLogger()->error(sprintf('Import failed: %s', $e->getMessage()));
                }

                throw new RuntimeException('Failed to import cards: ' . $e->getMessage(), 0, $e);
            }

            return [
                'processed'  => $processedCardsCount,
                'inserted'   => $insertedCardsCount,
                'updated'    => $updatedCardsCount,
                'skipped'    => $skippedCardsCount,
                'errors'     => $errorCount,
                'prices'     => $updatedPricesCount,
                'duel_ranks' => $updatedMTGTop8RanksCount,
            ];
        } finally {
            // Release the lock
            $lock->release();
        }
    }

    /**
     * Apply the collected lowest prices to MTGSourceCard entities, in batches of BATCH_SIZE.
     *
     * Rules:
     * - group by card name (entity nameEN)
     * - only update when avg != 0
     * - call updateMValueWithNewPrices(eur, usd)
     *
     * @param array<string, array{usd: numeric-string, eur: numeric-string, avg: numeric-string, tix: numeric-string}> $lowestAveragePricesByName
     *
     * @return int Number of card prices updated (i.e., entities for which updateMValueWithNewPrices() was called)
     */
    private function applyLowestAveragePricesToCards(
        array $lowestAveragePricesByName,
        ?callable $progressCallback = null,
        int $processedCardsCount = 0,
        int $insertedCardsCount = 0,
        int $updatedCardsCount = 0,
        int $skippedCardsCount = 0,
        int &$pricesCount = 0,
        int $errorCount = 0,
        int $updatedMTGTop8RanksCount = 0,
    ): int
    {
        $this->getLogger()->debug(sprintf(
            '[PRICES] Starting DB prices update from collected map (names=%d), persisting in batches of %d...',
            count($lowestAveragePricesByName),
            self::BATCH_SIZE
        ));

        if (count($lowestAveragePricesByName) === 0) {
            $this->getLogger()->debug('[PRICES] No prices found in JSON: skipping DB prices update.');

            return 0;
        }

        $names = array_keys($lowestAveragePricesByName);

        $persisted = 0;
        $processed = 0;

        for ($offset = 0, $offsetMax = count($names); $offset < $offsetMax; $offset += self::BATCH_SIZE) {
            $chunkNames = array_slice($names, $offset, self::BATCH_SIZE);

            /** @var list<MTGSourceCard> $cards */
            $cards = $this->entityManager
                ->getRepository(MTGSourceCard::class)
                ->createQueryBuilder('c')
                ->where('c.nameEN IN (:names)')
                ->setParameter('names', $chunkNames)
                ->getQuery()
                ->getResult();

            foreach ($cards as $card) {
                $cardNameEN = $card->getNameEN();
                ++$processed;

                if (! isset($lowestAveragePricesByName[$cardNameEN])) {
                    continue;
                }

                $avg = $lowestAveragePricesByName[$cardNameEN]['avg'];
                $modified = false;

                if ((float)$avg !== 0.0) {
                    $card->setLatestMValue((string)$avg);
                    $card->updateMValueWithNewPrice((string)$avg);
                    $modified = true;
                }

                if ((float)$lowestAveragePricesByName[$cardNameEN]['tix'] !== 0.0) {
                    $card->setMTGOPrice($lowestAveragePricesByName[$cardNameEN]['tix']);
                    $modified = true;
                }

                if ($modified === true) {
                    $this->entityManager->persist($card);
                    ++$persisted;
                    // Keep the same counters the CLI already prints (pricesCount is the one that should move now)
                    ++$pricesCount;
                }

                if ($progressCallback !== null && $processed % 100 === 0) {
                    $progressCallback(
                        $processedCardsCount,
                        $insertedCardsCount,
                        $updatedCardsCount,
                        $skippedCardsCount,
                        $pricesCount,
                        $errorCount,
                        $updatedMTGTop8RanksCount
                    );
                }
            }

            $this->entityManager->flush();
            $this->entityManager->clear();
        }

        $this->getLogger()->debug(sprintf(
            '[PRICES] Finished DB prices update. Processed entities=%d, updated=%d',
            $processed,
            $persisted
        ));

        return $persisted;
    }

    /**
     * Calculate legality flags based on card properties.
     *
     * @param array<string, mixed> $card A JSON card source converted into a PHP array
     * @param DateTimeImmutable $currentDate The exact date at which the model was instanciated
     *
     * @return array{is_legal_duel_commander: bool, is_legal_commander: bool, is_legal_2hg: bool}
     */
    private function calculateASCardVariantsLegality(array $card, DateTimeImmutable $currentDate): array
    {
        $legalities = isset($card['legalities']) && is_array($card['legalities']) ? $card['legalities'] : [];
        $borderColor = isset($card['border_color']) && is_string($card['border_color']) ? $card['border_color'] : '';
        $games = isset($card['games']) && is_array($card['games']) ? $card['games'] : [];
        $typeLine = isset($card['type_line']) && is_string($card['type_line']) ? $card['type_line'] : '';
        $oracleText = isset($card['oracle_text']) && is_string($card['oracle_text']) ? $card['oracle_text'] : '';
        $releasedAt = isset($card['released_at']) && is_string($card['released_at']) ? $card['released_at'] : '';
        $securityStamp = isset($card['security_stamp']) && is_string($card['security_stamp']) ? $card['security_stamp'] : '';
        $nameEN = isset($card['name']) && is_string($card['name']) ? $card['name'] : '';

        // Default: legal if vintage is legal or restricted
        $isLegal = isset($legalities['vintage']) && in_array($legalities['vintage'], ['legal', 'restricted'], true);

        // Special case: Shahrazad is always legal
        if ($nameEN === 'Shahrazad') {
            return [
                'is_legal_duel_commander' => true,
                'is_legal_commander'      => true,
                'is_legal_2hg'            => true,
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
            'is_legal_duel_commander' => $isLegal,
            'is_legal_commander'      => $isLegal,
            'is_legal_2hg'            => $isLegal,
        ];
    }

    /**
     * Calculate if a card is command zone eligible,
     * based on a JSON card source converted into a PHP array.
     *
     * @param array<string, mixed> $sourceCard A JSON source card converted into a PHP array
     *
     * @return bool True if the card can be a commander, false otherwise
     */
    private function canCardBeACommander(array $sourceCard): bool
    {
        $typeLine = isset($sourceCard['type_line']) && is_string($sourceCard['type_line']) ? $sourceCard['type_line'] : '';
        $oracleText = isset($sourceCard['oracle_text']) && is_string($sourceCard['oracle_text']) ? $sourceCard['oracle_text'] : '';
        $name = $sourceCard['name'] ?? '';

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
            if (array_any($requiredTypes, static fn ($type) => mb_stripos($typeLine, $type) !== false)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Update the in-memory map of lowest average (50/50 USD/EUR) prices per card name.
     *
     * @param array<string, array{usd: numeric-string, eur: numeric-string, avg: numeric-string, tix: numeric-string}> $lowestAveragePricesByName
     * @param array<string, mixed> $card Raw Scryfall card JSON decoded as array
     */
    private function collectLowestAveragePriceByNameFromRawScryfallCard(array &$lowestAveragePricesByName, array $card): void
    {
        // Discard this card as a price candidate if it doesn't have a name
        if (! isset($card['name']) || ! is_string($card['name']) || $card['name'] === '') {
            return;
        }

        // Discard this card as a price candidate if it is a gold-bordered card (exception to prices, as they're all cheaper illegal versions of the same card)'
        if (isset($card['border_color']) && ($card['border_color'] === 'gold' || $card['border_color'] === 'yellow')) {
            return;
        }

        // Discard this card if it is from a very, overly rare set that only has reprints and has unstable prices
        $set = isset($card['set']) && is_string($card['set']) ? $card['set'] : '';
        // Note: we're still including the gold-bordered sets, as a double-failsafe
        if (in_array($set, ['sum', 'ced', 'cei', 'wc97', 'wc98', 'wc99', 'wc00', 'wc01', 'wc02', 'wc03', 'wc04', '30a', 'pjjt'], true)) {
            return;
        }

        // Also exclude by Scryfall set type
        $setType = isset($card['set_type']) && is_string($card['set_type']) ? $card['set_type'] : '';
        if (in_array($setType, ['treasure_chest', 'memorabilia'], true)) {
            return;
        }

        // Each price should be compared against similar currency source, all finishes/treatments considered.
        /** @var array{
         *     tix?: float|string|null,
         *     eur?: float|string|null,
         *     usd?: float|string|null,
         *     eur_foil?: float|string|null,
         *     usd_foil?: float|string|null,
         *     eur_etched?: float|string|null,
         *     usd_etched?: float|string|null
         * } $prices
         */
        $prices = is_array($card['prices'] ?? null) ? $card['prices'] : [];

        $finalPriceEUR = (float)(
            $prices['eur']
            ?? $prices['eur_foil']
            ?? $prices['eur_etched']
            ?? 0.0
        );

        $finalPriceUSD = (float)(
            $prices['usd']
            ?? $prices['usd_foil']
            ?? $prices['usd_etched']
            ?? 0.0
        );

        if (isset($prices['eur_foil']) && $prices['eur_foil'] < $finalPriceEUR) {
            $finalPriceEUR = $prices['eur_foil'];
        }

        if (isset($prices['eur_etched']) && $prices['eur_etched'] < $finalPriceEUR) {
            $finalPriceEUR = $prices['eur_etched'];
        }

        if (isset($prices['usd_etched']) && $prices['usd_etched'] < $finalPriceUSD) {
            $finalPriceUSD = $prices['usd_etched'];
        }

        $usd = $this->normalizePriceToNumericString($finalPriceUSD);
        $eur = $this->normalizePriceToNumericString($finalPriceEUR);
        $tix = $this->normalizePriceToNumericString($prices['tix'] ?? null);
        $usdVsZero = bccomp($usd, '0', 2);
        $eurVsZero = bccomp($eur, '0', 2);

        // Do not collect / store any price candidate if prices are all 0.
        if ($usdVsZero === 0 && $eurVsZero === 0 && (float)$tix === 0.0) {
            return;
        }

        if ($usdVsZero <= 0 && $eurVsZero > 0) {
            $avg = $eur;
        } elseif ($usdVsZero > 0 && $eurVsZero <= 0) {
            $avg = $usd;
        } else {
            $sum = bcadd($usd, $eur, 2);
            $avg = bcdiv($sum, '2', 2);
        }

        $name = $card['name'];

        if (! isset($lowestAveragePricesByName[$name])) {
            $lowestAveragePricesByName[$name] = [
                'usd' => '0.00',
                'eur' => '0.00',
                'avg' => '0.00',
                'tix' => '0.00',
            ];
        }

        if (isset($card['border_color'], $card['set_type']) && ! in_array($card['set_type'], ['alchemy', 'token', 'memorabilia', 'minigame'], true)) {
            // AVG
            if (
                bccomp($avg, '0.00', 2) === 1
                && (
                    bccomp($lowestAveragePricesByName[$name]['avg'], '0.00', 2) === 0
                    || bccomp($avg, $lowestAveragePricesByName[$name]['avg'], 2) === -1
                )
            ) {
                $lowestAveragePricesByName[$name]['avg'] = $avg;
            }

            // USD
            if (
                bccomp($usd, '0.00', 2) === 1
                && (
                    bccomp($lowestAveragePricesByName[$name]['usd'], '0.00', 2) === 0
                    || bccomp($usd, $lowestAveragePricesByName[$name]['usd'], 2) === -1
                )
            ) {
                $lowestAveragePricesByName[$name]['usd'] = $usd;
            }

            // EUR
            if (
                bccomp($eur, '0.00', 2) === 1
                && (
                    bccomp($lowestAveragePricesByName[$name]['eur'], '0.00', 2) === 0
                    || bccomp($eur, $lowestAveragePricesByName[$name]['eur'], 2) === -1
                )
            ) {
                $lowestAveragePricesByName[$name]['eur'] = $eur;
            }
        }

        // TIX (independent of borders)
        if (
            bccomp($tix, '0.00', 2) === 1
            && (
                bccomp($lowestAveragePricesByName[$name]['tix'], '0.00', 2) === 0
                || bccomp($tix, $lowestAveragePricesByName[$name]['tix'], 2) === -1
            )
        ) {
            $lowestAveragePricesByName[$name]['tix'] = $tix;
        }
    }

    /**
     * Calculate if a card is eligible for multiple command zones,
     * based on a JSON card source converted into a PHP array.
     *
     * @param array<string, mixed> $sourceCard A source card converted into a PHP array
     * @param bool $isCommandZoneEligible
     *
     * @return bool True if the card can trigger multiple commanders, false otherwise
     */
    private function doesSourceCardTriggerMultipleCommanders(array $sourceCard, bool $isCommandZoneEligible): bool
    {
        if (! $isCommandZoneEligible) {
            return false;
        }

        $oracleText = isset($sourceCard['oracle_text']) && is_string($sourceCard['oracle_text']) ? $sourceCard['oracle_text'] : '';
        $typeLine = isset($sourceCard['type_line']) && is_string($sourceCard['type_line']) ? $sourceCard['type_line'] : '';
        $partnerKeywords = ['Partner', 'Friends forever', 'Choose a Background'];

        if (array_any($partnerKeywords, static fn ($keyword) => mb_stripos($oracleText, $keyword) !== false)) {
            return true;
        }

        $partnerTypes = ['Background', 'Time Lord Doctor'];

        return array_any($partnerTypes, static fn ($type) => mb_stripos($typeLine, $type) !== false);
    }

    /**
     * @return string Raw HTML
     */
    private function fetchHtml(string $url): string
    {
        /**
         * Pretend to be a Firefox browser, we are a bot, but a fair one, with one call only, not a rainfall of calls.
         */
        $context = stream_context_create([
            'http'  => [
                'method'  => 'GET',
                'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64)',
                'Accept: text/html,application/xhtml+xml',
                'Accept-Language: en-US,en;q=0.9',
                'Connection: close',
                'timeout' => 30,
            ],
            'https' => [
                'method'  => 'GET',
                'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64)',
                'Accept: text/html,application/xhtml+xml',
                'Accept-Language: en-US,en;q=0.9',
                'Connection: close',
                'timeout' => 30,
            ],
            'ssl'   => [
                'verify_peer'      => true,
                'verify_peer_name' => true,
            ],
        ]);

        $html = @file_get_contents($url, false, $context);

        // Try CURL instead
        if (! is_string($html) || $html === '') {

            $ch = curl_init($url);

            if ($ch === false) {
                throw new RuntimeException(sprintf('Failed to fetch HTML from: %s', $url));
            }

            $setOptions = curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_MAXREDIRS      => 10,
                CURLOPT_TIMEOUT        => 30,
                CURLOPT_USERAGENT      => 'Mozilla/5.0',
                CURLOPT_HTTPHEADER     => [
                    'Accept: text/html',
                ],
            ]);

            if ($setOptions !== true) {
                throw new RuntimeException(sprintf('Failed to fetch HTML from: %s (%s)', $url, curl_error($ch)));
            }

            $html = curl_exec($ch);

            if ($html === false) {
                throw new RuntimeException(sprintf('Failed to fetch HTML from: %s (%s)', $url, curl_error($ch)));
            }

            curl_close($ch);
        }

        return (string)$html;
    }

    /**
     * Format a value for logging purposes.
     *
     * @param mixed $value the value to format, any given PHP type
     *
     * @throws JsonException
     *
     * @return string the formatted value for logging as a string
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

        if (is_string($value) || $value instanceof Stringable || (is_object($value) && method_exists($value, '__toString'))) {
            // Truncate very long strings (like JSON)
            if (mb_strlen((string)$value) > 100) {
                return mb_substr((string)$value, 0, 97) . '...';
            }

            return (string)$value;
        }

        if (is_array($value)) {
            return json_encode($value, JSON_THROW_ON_ERROR);
        }

        return '[non stringable value]';
    }

    /**
     * Calculate color identity flags, based on a simplified color identity array.
     *
     * @param string[] $colorIdentity A simplified color identity array of strings (e.g., ['R', 'G'])
     *
     * @return array{isBlack: bool, isBlue: bool, isColorless: bool, isGreen: bool, isRed: bool, isWhite: bool} an array of color identity flags
     */
    private function getCardColorIdentityFlagsFromColorIdentityArray(array $colorIdentity): array
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
     * Calculate special legality flags based on command zone eligibility boolean flags.
     * Respectively set to true if the card is legal in the given format AND is command zone eligible.
     *
     * @param bool $isLegal2HG
     * @param bool $isLegalDuel
     * @param bool $isLegalMulti
     * @param bool $isCommandZoneEligible
     *
     * @return array{is_legal_2hg_special: bool, is_legal_duel_commander_special: bool, is_legal_commander_special: bool} An array of legality flags
     *
     * @see MTGScryfallDefaultCardsSourceDataTransformerModelV1::canCardBeACommander()
     */
    private function getCardSpecialLegalityFromFlags(
        bool $isLegal2HG,
        bool $isLegalDuel,
        bool $isLegalMulti,
        bool $isCommandZoneEligible
    ): array
    {
        return [
            'is_legal_2hg_special'            => $isLegal2HG && $isCommandZoneEligible,
            'is_legal_duel_commander_special' => $isLegalDuel && $isCommandZoneEligible,
            'is_legal_commander_special'      => $isLegalMulti && $isCommandZoneEligible,
        ];
    }

    /**
     * Find the JSON file in the cards source directory.
     * Returns the path to the JSON file.
     *
     * @throws RuntimeException
     *
     * @return string the path to the latest downloaded JSON file
     */
    private function getLatestScryfallSourceJsonFile(): string
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
     * @return Logger
     */
    private function getLogger(): Logger
    {
        if ($this->fileLogger === null) {
            throw new RuntimeException('Logger not initialized');
        }

        return $this->fileLogger;
    }

    /**
     * Get the maximum copies of a card, based on Oracle text.
     *
     * Rules:
     * - Default: 1
     * - -1 means unlimited
     * - Basic cards: unlimited
     * - "any number of cards named": unlimited
     * - "up to N cards named": N (1–20)
     *
     * @param array<string, mixed> $card A JSON card source converted into a PHP array
     *
     * @return int
     */
    private function getMaxCopiesForCard(array $card): int
    {
        if (
            empty($card['type_line'])
            || empty($card['oracle_text'])
            || ! is_string($card['type_line'])
            || ! is_string($card['oracle_text'])
        ) {
            return 1;
        }

        // 1. Basic cards → unlimited
        if (mb_stristr($card['type_line'], 'Basic') !== false) {
            return -1;
        }

        $oracleText = $card['oracle_text'];

        // 2. Any number of cards named → unlimited
        if (
            mb_stristr(
                $oracleText,
                'A deck can have any number of cards named'
            ) !== false
        ) {
            return -1;
        }

        // 3. Up to N cards named (1–20)
        if (
            preg_match(
                '/A deck can have up to (\w+) cards named/iu',
                $oracleText,
                $matches
            )
        ) {
            $numberMap = [
                'one'       => 1,
                'two'       => 2,
                'three'     => 3,
                'four'      => 4,
                'five'      => 5,
                'six'       => 6,
                'seven'     => 7,
                'eight'     => 8,
                'nine'      => 9,
                'ten'       => 10,
                'eleven'    => 11,
                'twelve'    => 12,
                'thirteen'  => 13,
                'fourteen'  => 14,
                'fifteen'   => 15,
                'sixteen'   => 16,
                'seventeen' => 17,
                'eighteen'  => 18,
                'nineteen'  => 19,
                'twenty'    => 20,
            ];

            $word = mb_strtolower($matches[1]);

            if (isset($numberMap[$word])) {
                return $numberMap[$word];
            }
        }

        // 4. Default MTG rule
        return 1;
    }

    /**
     * This method determines the maximum timeline legality of a card.
     * The maximum timeline legality is based on the card's legalities in various formats.
     *
     * By default, a card is considered "printed", this is the default value.
     * Then, the card is considered "funny" if its "border_color" field has the value "silver"
     * or if its field "security_stamp" has the value "acorn".
     * Then, if a card name is
     * Then, if the card field "legalities" contains either "restricted" or "legal" in vintage, it is classified as "eternal".
     * Then, it is classified as "eternal" also if all of the following:
     * - the card field "legalities" contains "banned" in vintage
     * - the card field "games" contains "paper"
     * - the card field "border_color" is not "silver"
     * - the card field "type_line" does not contain any of the following substrings: "Conspiracy", "Attraction", "Sticker", "Dungeon", "Contraption"
     * - the card field "oracle_text" does not contain the substring "playing for ante", "sticker", or both "open" or "visit" and "attraction"
     * - the card field "released_at" is not in the future
     * - the card field "security_stamp" is not "acorn" or "heart"
     * - the card name is not "Shahrazad"
     * Then, if the card field "legalities" contains either "banned" "restricted" or "legal" in modern, it is classified as "modern".
     * Then, if the card field "legalities" contains either "banned" "restricted" or "legal" in pioneer, it is classified as "pioneer".
     * Finally, if the card field "legalities" contains either "banned" "restricted" or "legal" in standard, it is classified as "standard".
     * Special case: if the card field "released_at" is in the future, the card is always classified as "unranked".
     *
     * @param array<string, mixed> $sourceCard the Scryfall card data
     * @param DateTimeImmutable $currentDate the current date for comparison
     */
    private function getMaximumTimelineLegalityForSourceCard(array $sourceCard, DateTimeImmutable $currentDate): string
    {
        $borderColor = isset($sourceCard['border_color']) && is_string($sourceCard['border_color']) ? $sourceCard['border_color'] : '';
        $securityStamp = isset($sourceCard['security_stamp']) && is_string($sourceCard['security_stamp']) ? $sourceCard['security_stamp'] : '';
        $legalities = isset($sourceCard['legalities']) && is_array($sourceCard['legalities']) ? $sourceCard['legalities'] : [];
        $games = isset($sourceCard['games']) && is_array($sourceCard['games']) ? $sourceCard['games'] : [];
        $typeLine = isset($sourceCard['type_line']) && is_string($sourceCard['type_line']) ? $sourceCard['type_line'] : '';
        $oracleText = isset($sourceCard['oracle_text']) && is_string($sourceCard['oracle_text']) ? $sourceCard['oracle_text'] : '';
        $nameEN = isset($sourceCard['name']) && is_string($sourceCard['name']) ? $sourceCard['name'] : '';

        // Check for "printed" classification for gold-bordered cards
        if ($borderColor === 'gold') {
            return 'printed';
        }

        // Check named exceptions
        if (in_array($nameEN, ['Chaos Orb', 'Falling Star'], true)) {
            return 'printed';
        }

        // Check standard legality
        if (isset($legalities['standard']) && in_array($legalities['standard'], ['legal', 'restricted', 'banned'], true)) {
            return 'standard';
        }

        // Check pioneer legality
        if (isset($legalities['pioneer']) && in_array($legalities['pioneer'], ['legal', 'restricted', 'banned'], true)) {
            return 'pioneer';
        }

        // Check modern legality
        if (isset($legalities['modern']) && in_array($legalities['modern'], ['legal', 'restricted', 'banned'], true)) {
            return 'modern';
        }

        // Check if card qualifies for "eternal" through legal/restricted in vintage
        if (isset($legalities['vintage']) && in_array($legalities['vintage'], ['legal', 'restricted'], true)) {
            return 'eternal';
        }

        // Check if card qualifies for "eternal" through banned in vintage but meets all other criteria
        if (isset($legalities['vintage']) && $legalities['vintage'] === 'banned') {
            $meetsEternalCriteria = true;

            // Check all conditions for eternal classification with banned vintage status
            if (! in_array('paper', $games, true)) {
                $meetsEternalCriteria = false;
            }

            $forbiddenTypes = ['Conspiracy', 'Attraction', 'Sticker', 'Dungeon', 'Contraption'];
            foreach ($forbiddenTypes as $type) {
                if (mb_stripos($typeLine, $type) !== false) {
                    $meetsEternalCriteria = false;

                    break;
                }
            }

            if (mb_stripos($oracleText, 'playing for ante') !== false) {
                $meetsEternalCriteria = false;
            }

            if (mb_stripos($oracleText, 'sticker') !== false) {
                $meetsEternalCriteria = false;
            }

            $hasOpenOrVisit = mb_stripos($oracleText, 'open') !== false || mb_stripos($oracleText, 'visit') !== false;
            $hasAttraction = mb_stripos($oracleText, 'attraction') !== false;
            if ($hasOpenOrVisit && $hasAttraction) {
                $meetsEternalCriteria = false;
            }

            if (in_array($securityStamp, ['acorn', 'heart'], true)) {
                $meetsEternalCriteria = false;
            }

            if ($nameEN === 'Shahrazad') {
                $meetsEternalCriteria = true;
            }

            if ($meetsEternalCriteria === true) {
                return 'eternal';
            }
        }

        // Special case: if released_at is in the future, return "unranked", but NOT a funny card either
        $releasedAt = isset($sourceCard['released_at']) && is_string($sourceCard['released_at']) ? $sourceCard['released_at'] : '';
        if (! empty($releasedAt)) {
            try {
                $releaseDate = new DateTimeImmutable($releasedAt);
                if ($releaseDate > $currentDate && $borderColor !== 'silver' && $securityStamp !== 'acorn') {
                    return 'unranked';
                }
            } catch (\Exception) {
                // If date parsing fails, continue with other checks.
                // Date is not mandatory here, only for future cards that are certain to be so.
            }
        }

        // Check for "funny" classification (silver border or acorn stamp)
        if ($borderColor === 'silver' || $securityStamp === 'acorn') {
            return 'funny';
        }

        // Default classification for cards that don't meet any of the above criteria
        return 'printed';
    }

    /**
     * Prepare data for update operation: determine which fields need to be updated.
     *
     * @param array<string, mixed> $currentSourceCard the new card data to compare against, from source JSON
     * @param array<string, mixed> $existingDBCard the existing card data from the database to compare against
     * @param DateTimeImmutable $currentDate the exact date at which the model was instanciated
     *
     * @throws DateMalformedStringException|JsonException
     *
     * @return array<string, mixed> an array of fields keys to update, with their new values
     */
    private function getSourceCardFieldsToUpdateAsArray(array $currentSourceCard, array $existingDBCard, DateTimeImmutable $currentDate): array
    {
        $updateData = [];

        // Check if we should update first printed date (only if new card is earlier or card is legal nowhere)
        if (
            ! isset(
                $currentSourceCard['first_printed_at'],
                $existingDBCard['first_printed_at'],
                $currentSourceCard['is_legal_duel_commander'],
                $currentSourceCard['is_legal_commander'],
                $currentSourceCard['is_legal_2hg']
            )
            || ! is_string($currentSourceCard['first_printed_at'])
            || ! is_string($existingDBCard['first_printed_at'])
        ) {
            // Missing date fields - cannot compare, skip updating first printed info
            return $updateData;
        }

        $cardIsLegalNowhere = ! $currentSourceCard['is_legal_duel_commander'] &&
            ! $currentSourceCard['is_legal_commander'] &&
            ! $currentSourceCard['is_legal_2hg'];

        if (! $cardIsLegalNowhere) {
            $newDate = new DateTimeImmutable($currentSourceCard['first_printed_at']);
            $existingDate = new DateTimeImmutable($existingDBCard['first_printed_at']);

            if ($newDate < $existingDate) {
                // Found an earlier printing - update all printing-related fields
                $updateData['first_printed_at'] = $currentSourceCard['first_printed_at'];
                $updateData['first_printed_set_code'] = $currentSourceCard['first_printed_set_code'] ?? '';
                $updateData['first_printed_year'] = $currentSourceCard['first_printed_year'] ?? 0;
                $updateData['scryfall_id'] = $currentSourceCard['scryfall_id'] ?? '';
                $updateData['scryfall_uri'] = $currentSourceCard['scryfall_uri'] ?? '';
            }
        }

        // Update legalities if new card is legal and existing is not
        $possibleLegalityFields = [
            'is_legal_duel_commander'         => 'is_legal_duel_commander',
            'is_legal_commander'              => 'is_legal_commander',
            'is_legal_2hg'                    => 'is_legal_2hg',
            'is_legal_duel_commander_special' => 'is_legal_duel_commander_special',
            'is_legal_commander_special'      => 'is_legal_commander_special',
            'is_legal_2hg_special'            => 'is_legal_2hg_special',
        ];

        // Only update if new legality is higher than existing one, i.e., from 0 to 1 (for now in this model)
        foreach ($possibleLegalityFields as $possibleLegalityFieldName) {
            if (
                isset(
                    $currentSourceCard[$possibleLegalityFieldName],
                    $existingDBCard[$possibleLegalityFieldName]
                )
                && is_string($existingDBCard[$possibleLegalityFieldName])
                && is_string($currentSourceCard[$possibleLegalityFieldName])
                && (int)$existingDBCard[$possibleLegalityFieldName] < (int)$currentSourceCard[$possibleLegalityFieldName]
            ) {
                $updateData[$possibleLegalityFieldName] = $currentSourceCard[$possibleLegalityFieldName];
            }
        }

        // Check if these fields actually changed before updating
        // NOTE: scryfall_id and scryfall_uri are NOT included here because they're printing-specific
        // and should only be updated when first_printed_at is updated
        $fieldsToCheck = [
            'name_en',
            'flavor_of_name_en',
            'alternate_name_en',
            'types',
            'oracle_text',
            'image_url',
            'mana_value',
            'color_identity',
            'is_command_zone_eligible',
            'is_multiple_command_zone_eligible',
            'multi_cz_type',
            'max_copies',
            'is_black',
            'is_blue',
            'is_colorless',
            'is_green',
            'is_red',
            'is_white',
            'maximum_timeline_legality',
            'cedhrank',
            'ffarank',
            'duel_rank',
            'is_digital_only',
        ];

        foreach ($fieldsToCheck as $field) {
            // Convert existing card value to match new data format for comparison
            $existingValue = $existingDBCard[$field] ?? null;
            $newValue = $currentSourceCard[$field] ?? null;

            // Special handling for maximum_timeline_legality field
            // Check if both values are valid timeline legalities
            if (
                ($field === 'maximum_timeline_legality')
                && is_string($existingValue) && is_string($newValue)
                && isset(MTGPointsListModelV1::TIMELINE_PRECEDENCES[$existingValue], MTGPointsListModelV1::TIMELINE_PRECEDENCES[$newValue])
            ) {
                // Only update if new value has higher precedence than existing value
                $existingPrecedence = MTGPointsListModelV1::TIMELINE_PRECEDENCES[$existingValue];
                $newPrecedence = MTGPointsListModelV1::TIMELINE_PRECEDENCES[$newValue];

                if ($newPrecedence <= $existingPrecedence) {
                    continue; // Skip update if new value doesn't have higher precedence
                }
            }

            // Special rule for is_digital_only:
            // Once a card has been proven NOT digital-only (i.e. we parse a printing with digital=false),
            // we must keep is_digital_only = 0 forever, as it exists at least on paper (never allow 0 -> 1).
            if ($field === 'is_digital_only') {
                $existingDigitalOnly = (int)(bool)$existingValue;
                $newDigitalOnly = (int)(bool)$newValue;

                if ($existingDigitalOnly === 0 && $newDigitalOnly === 1) {
                    continue; // never set back to true
                }

                $existingValue = $existingDigitalOnly;
                $newValue = $newDigitalOnly;
            }

            // Special handling for different data types
            if ($field === 'mana_value' && is_numeric($existingValue)) {
                $existingValue = (float)$existingValue;
            } elseif ($field === 'color_identity') {
                // Normalize JSON comparison - decode existing and compare arrays
                $existingArray = is_string($existingValue) ? json_decode($existingValue, true, 512, JSON_THROW_ON_ERROR) : [];
                $newArray = is_string($newValue) ? json_decode($newValue, true, 512, JSON_THROW_ON_ERROR) : [];

                // Compare as arrays, not as JSON strings
                if ($existingArray === $newArray) {
                    continue; // Skip this field - no actual change
                }
            } elseif (
                in_array(
                    $field,
                    [
                        'is_command_zone_eligible',
                        'is_multiple_command_zone_eligible',
                        'is_black',
                        'is_blue',
                        'is_colorless',
                        'is_green',
                        'is_red',
                        'is_white',
                    ],
                    true
                )
            ) {
                $existingValue = (int)(bool)$existingValue;
            }

            // Only add to update if value has changed
            if ($existingValue !== $newValue) {
                $updateData[$field] = $newValue;
            }

            // Don't change imageurl if it's empty
            if ($field === 'image_url' && ((empty($newValue) && ! empty($existingDBCard[$field])) || (isset($currentSourceCard['digital']) && (bool)$currentSourceCard['digital'] === true))) {
                unset($updateData[$field]);
            }
        }

        // Only add updated_at timestamp if there are actual changes
        if (count($updateData) > 0) {
            $updateData['updated_at'] = $currentDate->format('Y-m-d H:i:s');
        }

        return $updateData;
    }

    /**
     * Calculate multi-commander zone type, based on a JSON card source converted into a PHP array.
     *
     * Checks for specific keywords in oracle text and type line:
     * - "Doctor's companion" -> 'doctors_companion'
     * - "Choose a Background" -> 'choose_a_background'
     * - "Friends forever" -> 'friends_forever'
     * - "Partner" -> 'partner'
     * - Type line containing "Time Lord Doctor" -> 'doctors_companion'
     * - Type line containing "Background" -> 'choose_a_background'.
     *
     * @param array<string, mixed> $sourceCard a JSON card source converted into a PHP array
     * @param bool $isCommandZoneEligible
     *
     * @return string the multi-command zone type identifier, or empty string if none match by default
     */
    private function getSourceCardMultipleCommandZoneType(array $sourceCard, bool $isCommandZoneEligible): string
    {
        if (! $isCommandZoneEligible) {
            return '';
        }

        $oracleText = isset($sourceCard['oracle_text']) && is_string($sourceCard['oracle_text']) ? $sourceCard['oracle_text'] : '';
        $typeLine = isset($sourceCard['type_line']) && is_string($sourceCard['type_line']) ? $sourceCard['type_line'] : '';

        // Check oracle text first (more specific)
        if (mb_stripos($oracleText, "Doctor's companion") !== false) {
            return 'doctors_companion';
        }

        if (mb_stripos($oracleText, 'Choose a Background') !== false) {
            return 'choose_a_background';
        }

        if (mb_stripos($oracleText, 'Partner with') !== false) {
            return 'partner_with';
        }

        // Handle 'Partner—[Type]' dynamically
        if (preg_match('/Partner\s*—\s*([^(]+)\(?/ui', $oracleText, $matches)) {
            $typeText = $matches[1];

            // Remove punctuation
            $typeText = preg_replace('/[^\p{L}\p{N}\s]+/u', '', $typeText);

            // Replace spaces with underscores and lowercase
            $slug = mb_strtolower(str_replace(' ', '_', mb_trim((string)$typeText)));

            return 'partner_type_' . $slug;
        }

        if (mb_stripos($oracleText, 'Partner') !== false) {
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
     * This method initialized the database table for MTG Source Activity History.
     *
     * What it does is it takes the unique source identifier for this import session
     * and creates an entry in the database table for the matching SourceActivityHistoryInterface
     * to track this import session and prepares the line.
     *
     * @param DateTimeImmutable $currentDate the exact date at which the model was instanciated
     * @param string $startedFrom Source identifier for where the import was started from (e.g., 'cli', 'web'), @see AbstractSourceActivityHistory
     * @param string $logFilePath The log file name generated for this import session
     *
     * @return void
     */
    private function initializeDatabaseSourceActivityHistory(DateTimeImmutable $currentDate, string $startedFrom, string $logFilePath): void
    {
        $this->sourceActivityHistory
            ->setChannel(self::CHANNEL)
            ->setStartedAt(DateTime::createFromImmutable($currentDate))
            ->setStartedFrom($startedFrom)
            ->setLogFilePath($logFilePath);

        $this->entityManager->persist($this->sourceActivityHistory);
        $this->entityManager->flush();
    }

    /**
     * Initialize file fileLogger for this import session.
     * Creates the log file in the same directory as the JSON file and deletes any existing log files there.
     *
     * @param string $jsonFilePath the path to the JSON file being imported, @see services.yaml
     *
     * @return string The log file name generated for this import session
     */
    private function initializeLogger(string $jsonFilePath): string
    {
        /** @var array{dirname: string, basename: string, extension: string, filename: string} $pathInfo */
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

        return $logFileName;
    }

    /**
     * Load all existing cards into memory cache.
     * This allows for fast lookups during the import process.
     * This will work as long as the source file contains a manageable number of cards compared to available running memory.
     *
     * @throws Exception
     */
    private function loadExistingCardsCache(): void
    {
        $this->existingCardsCache = [];

        $result = $this->connection->executeQuery('SELECT * FROM ' . $this->tablePrefix . 'mtgsource_card');

        while ($row = $result->fetchAssociative()) {
            if (isset($row['name_en']) && is_string($row['name_en'])) {
                $this->existingCardsCache[$row['name_en']] = $row;
            }
        }
    }

    /**
     * Transform a Scryfall card to database format.
     *
     * @param array<string, mixed> $card A JSON card source converted into a PHP array
     * @param DateTimeImmutable $currentDate The exact date at which the model was instanciated
     *
     * @throws JsonException
     *
     * @return array<string, mixed>|null An array of transformed card data, or null if the card should be skipped
     */
    private function mapScryfallSourceCardDataToArray(array $card, DateTimeImmutable $currentDate): ?array
    {
        // Skip cards without oracle_id, as the reference identifier cannot be trusted otherwise
        if (! isset($card['oracle_id'], $card['name']) || ! is_string($card['oracle_id']) || ! is_string($card['name'])) {
            return null;
        }

        // Skip cards with lang != en and printed_name != name.
        // to ensure that the card image is in english.
        if (isset($card['lang'], $card['printed_name']) && $card['lang'] !== 'en' && $card['printed_name'] !== $card['name']) {
            return null;
        }

        // Extract basic fields
        $oracleId = $card['oracle_id'];
        $set = isset($card['set']) && is_string($card['set']) ? $card['set'] : '';
        $scryfallId = $card['id'] ?? null;
        // Name EN uses the first face flavour name if defined, otherwise the card flavour name if defined, otherwise the card name
        $nameEN = (isset($card['card_faces'][0]) && is_array($card['card_faces'][0]) && ! empty($card['card_faces'][0]['flavor_name'])) ? $card['card_faces'][0]['flavor_name'] : $card['flavor_name'] ?? $card['name']; // @phpstan-ignore-line
        // Flavor name of uses the card name if a flavor name is defined as main flavor name or first face flavor name
        $flavorOfNameEN = (isset($card['card_faces'][0]) && is_array($card['card_faces'][0]) && ! empty($card['card_faces'][0]['flavor_name'])) || ! empty($card['flavor_name']) ? $card['name'] : ''; // @phpstan-ignore-line
        // Alternate name EN uses the card's first face name if a flavor name defined or a first face name is defined
        $alternateNameEN = (isset($card['card_faces'][0]) && is_array($card['card_faces'][0]) && (! empty($card['card_faces'][0]['flavor_name']) || ! empty($card['card_faces'][0]['name']))) // @phpstan-ignore-line
            ? $card['card_faces'][0]['name'] ?? '' : '';

        // One exception for SLX cards - take the printed name as the alternate name
        if (isset($card['printed_name'], $card['lang']) && $flavorOfNameEN === '' && $card['lang'] === 'en' && $card['printed_name'] !== $nameEN && mb_strtolower($set) === 'sld') {
            $flavorOfNameEN = $card['printed_name'];
        }

        $manaValue = (float)(isset($card['cmc']) && is_numeric($card['cmc']) ? $card['cmc'] : 0.0);
        $colorIdentity = isset($card['color_identity']) && is_array($card['color_identity']) && $card['color_identity'] ? $card['color_identity'] : [];

        if (count($colorIdentity) === 0) {
            $colorIdentity = ['C'];
        }

        $types = isset($card['type_line']) && is_string($card['type_line']) ? $card['type_line'] : '';

        $oracleTexts = [isset($card['oracle_text']) && is_string($card['oracle_text']) ? $card['oracle_text'] : ''];
        if (isset($card['card_faces']) && is_array($card['card_faces'])) {
            foreach ($card['card_faces'] as $cardFace) {
                if (is_array($cardFace) && isset($cardFace['oracle_text']) && is_string($cardFace['oracle_text'])) {
                    $oracleTexts[] = $cardFace['oracle_text'];
                }
            }
        }
        $oracleText = implode("\n", $oracleTexts);

        $scryfallUri = $card['scryfall_uri'] ?? '';
        $releasedAt = $card['released_at'] ?? '';
        $borderColor = isset($card['border_color']) && is_string($card['border_color']) ? $card['border_color'] : '';

        if ($borderColor !== 'gold' && $borderColor !== 'yellow') {
            // The image URL is the main image from Scryfall (normal size), otherwise the first face image if defined
            $imageURL = (array_key_exists('image_uris', $card) && is_array($card['image_uris']) && ! empty($card['image_uris']['normal']))
                ? $card['image_uris']['normal']
                : (
                    (isset($card['card_faces'][0]) && is_array($card['card_faces'][0]) && array_key_exists('image_uris', $card['card_faces'][0]) && is_array($card['card_faces'][0]['image_uris']) && ! empty($card['card_faces'][0]['image_uris']['normal'])) // @phpstan-ignore-line
                    ? $card['card_faces'][0]['image_uris']['normal']
                    : ''
                );
        } else {
            $imageURL = '';
        }

        // Invalid color identity entry, skip the card
        if (array_any($colorIdentity, static fn ($identity) => ! is_string($identity))) {
            return null;
        }

        // Calculate legalities
        $legality = $this->calculateASCardVariantsLegality($card, $currentDate);
        $isCommandZoneEligible = $this->canCardBeACommander($card);
        $maximumTimelineLegality = $this->getMaximumTimelineLegalityForSourceCard($card, $currentDate);

        // Calculate special legalities
        $specialLegality = $this->getCardSpecialLegalityFromFlags(
            $legality['is_legal_2hg'],
            $legality['is_legal_duel_commander'],
            $legality['is_legal_commander'],
            $isCommandZoneEligible
        );

        // Calculate command zone flags
        $isMultipleCommandZoneEligibility = $this->doesSourceCardTriggerMultipleCommanders($card, $isCommandZoneEligible);
        $multiCZType = $this->getSourceCardMultipleCommandZoneType($card, $isCommandZoneEligible);

        // Calculate color flags
        $colorFlags = $this->getCardColorIdentityFlagsFromColorIdentityArray($colorIdentity); // @phpstan-ignore-line

        // Parse release date
        $firstPrintedAt = null;
        $firstPrintedYear = 0;

        try {
            if (is_string($releasedAt) && $releasedAt !== '') {
                $date = new DateTimeImmutable($releasedAt);
                $firstPrintedAt = $date->format('Y-m-d');
                $firstPrintedYear = (int)$date->format('Y');
            }
        } catch (\Exception) {
            $firstPrintedAt = $currentDate->format('Y-m-d');
            $firstPrintedYear = (int)$currentDate->format('Y');
        }

        // Calculate maximum copies of a card on the fly
        $maxCopies = $this->getMaxCopiesForCard($card);

        return [
            'oracle_id'                         => $oracleId,
            'scryfall_id'                       => $scryfallId,
            'name_en'                           => $nameEN,
            'flavor_of_name_en'                 => $flavorOfNameEN,
            'alternate_name_en'                 => $alternateNameEN,
            'oracle_text'                       => $oracleText,
            'types'                             => $types,
            'mana_value'                        => $manaValue,
            'color_identity'                    => json_encode($colorIdentity, JSON_THROW_ON_ERROR),
            'scryfall_uri'                      => $scryfallUri,
            'image_url'                         => $imageURL,
            'first_printed_at'                  => $firstPrintedAt,
            'first_printed_set_code'            => $set,
            'first_printed_year'                => $firstPrintedYear,
            'is_legal_duel_commander'           => (int)$legality['is_legal_duel_commander'],
            'is_legal_commander'                => (int)$legality['is_legal_commander'],
            'is_legal_2hg'                      => (int)$legality['is_legal_2hg'],
            'is_legal_duel_commander_special'   => (int)$specialLegality['is_legal_duel_commander_special'],
            'is_legal_commander_special'        => (int)$specialLegality['is_legal_commander_special'],
            'is_legal_2hg_special'              => (int)$specialLegality['is_legal_2hg_special'],
            'is_command_zone_eligible'          => (int)$isCommandZoneEligible,
            'is_multiple_command_zone_eligible' => (int)$isMultipleCommandZoneEligibility,
            'multi_cz_type'                     => $multiCZType,
            'max_copies'                        => $maxCopies,
            'points_base_quadruples'            => 0.0,
            'points_base_singleton'             => 0.0,
            'points_duel_commander'             => 0.0,
            'points_duel_commander_special'     => 0.0,
            'points_commander'                  => 0.0,
            'points_commander_special'          => 0.0,
            'points_2hg'                        => 0.0,
            'points_2hg_special'                => 0.0,
            'points_highlander'                 => 0.0,
            'points_modern'                     => 0.0,
            'points_pioneer'                    => 0.0,
            'points_standard'                   => 0.0,
            'is_black'                          => (int)$colorFlags['isBlack'],
            'is_blue'                           => (int)$colorFlags['isBlue'],
            'is_colorless'                      => (int)$colorFlags['isColorless'],
            'is_green'                          => (int)$colorFlags['isGreen'],
            'is_red'                            => (int)$colorFlags['isRed'],
            'is_white'                          => (int)$colorFlags['isWhite'],
            'maximum_timeline_legality'         => $maximumTimelineLegality,
            'mtgoprice'                         => 0.0,
            'mvalue_trend'                      => 0.0,
            'mvalue_count'                      => 0,
            'latest_mvalue'                     => 0.0,
            'ffarank'                           => $card['edhrec_rank'] ?? 0,
            'duel_rank'                         => 0,
            'cedhrank'                          => 0,
            'is_digital_only'                   => (int)(isset($card['digital']) && $card['digital'] === true),
        ];
    }

    private function normalizeNameForLookup(string $name): string
    {
        return mb_strtolower(mb_trim($name), 'UTF-8');
    }

    /**
     * Normalize Scryfall price input to a numeric-string with 2 decimals.
     *
     * @param mixed $value
     *
     * @return numeric-string
     */
    private function normalizePriceToNumericString(mixed $value): string
    {
        if (! is_string($value) || $value === '' || ! is_numeric($value)) {
            return '0.00';
        }

        return bcadd($value, '0', 2);
    }

    /**
     * Parse commander names (anchor text) from MTGTop8 cEDH popularity HTML, top-to-bottom.
     *
     * Expected fragments like:
     *   <div ... class=S14><a href=archetype?...>Aragorn, King Of Gondor</a></div>
     *
     * @return list<string>
     */
    private function parseMTGTop8CommanderNamesFromHtml(string $html): array
    {
        // Grab anchor text for archetype links in document order.
        $pattern = '/<a[^>]*href\s*=\s*["\']?archetype\?[^>]*>\s*([^<]+)\s*<\/a>/ui';

        if (! preg_match_all($pattern, $html, $matches)) {
            return [];
        }

        $names = [];

        foreach ($matches[1] as $raw) {
            $text = html_entity_decode(strip_tags($raw), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $text = mb_trim($text);

            if ($text === '') {
                continue;
            }

            $names[] = $text;
        }

        return $names;
    }

    /**
     * Process a batch of BATCH_SIZE cards found in source JSON (upsert).
     * Determining whether to insert, update or skip each card.
     *
     * @param array<int, array<string, mixed>> $batchSliceOfCards An array containing the current BATCH_SIZE cards to be processed
     * @param DateTimeImmutable $batchStartedAtDate The date at which the batch processing started
     *
     * @throws DateMalformedStringException|Exception|JsonException
     *
     * @return array{inserted: int, updated: int, skipped: int, prices: int, errors: int, duel_ranks: int}
     */
    private function processCardsUpdateBatchSlice(array $batchSliceOfCards, DateTimeImmutable $batchStartedAtDate): array
    {
        $insertedCount = 0;
        $updatedCount = 0;
        $skippedCount = 0;
        $pricesCount = 0;
        $errorCount = 0;
        $updatedMTGTop8RanksCount = 0;

        foreach ($batchSliceOfCards as $currentSourceCard) {

            if (empty($currentSourceCard['name_en'])) {
                $this->getLogger()->warning(
                    '[SKIP] Card with missing name_en - skipping',
                    [
                        'card_data' => $currentSourceCard,
                    ]
                );
                ++$skippedCount;

                continue;
            }

            if (empty($currentSourceCard['scryfall_id'])) {
                $this->getLogger()->warning(
                    '[SKIP] Card with missing scryfall_id - skipping',
                    [
                        'card_data' => $currentSourceCard,
                    ]
                );
                ++$skippedCount;

                continue;
            }

            if (empty($currentSourceCard['first_printed_set_code'])) {
                $this->getLogger()->warning(
                    '[SKIP] Card with missing first_printed_set_code - skipping',
                    [
                        'card_data' => $currentSourceCard,
                    ]
                );
                ++$skippedCount;

                continue;
            }

            /** @var string $oracleId */
            $oracleId = $currentSourceCard['oracle_id'] ?? '';
            /** @var string $cardName */
            $cardName = $currentSourceCard['name_en'];
            /** @var string $scryfallId */
            $scryfallId = $currentSourceCard['scryfall_id'];
            /** @var string $firstPrintedSetCode */
            $firstPrintedSetCode = $currentSourceCard['first_printed_set_code'];

            // Check cache instead of querying database
            if (! isset($this->existingCardsCache[$cardName])) {
                // Insert new card with timestamps
                $currentSourceCard['created_at'] = $batchStartedAtDate->format('Y-m-d H:i:s');
                $currentSourceCard['updated_at'] = $batchStartedAtDate->format('Y-m-d H:i:s');

                $this->connection->insert($this->tablePrefix . 'mtgsource_card', $currentSourceCard);

                // Add to cache for future lookups
                $this->existingCardsCache[$cardName] = $currentSourceCard;

                $this->getLogger()->info(
                    sprintf(
                        '[INSERT] Card "%s" (oracle_id: %s) inserted into database',
                        $cardName,
                        $oracleId
                    ),
                    [
                        'scryfall_id' => $scryfallId,
                        'set'         => $firstPrintedSetCode,
                    ]
                );

                ++$insertedCount;
            } else {
                // Update existing card
                $existingDBCard = $this->existingCardsCache[$cardName];
                $fieldsToUpdate = $this->getSourceCardFieldsToUpdateAsArray($currentSourceCard, $existingDBCard, $batchStartedAtDate);

                if (count($fieldsToUpdate) > 0) {
                    // Build list of changed fields for logging
                    $changedFields = [];
                    foreach ($fieldsToUpdate as $field => $newValue) {
                        if ($field === 'updated_at') {
                            continue; // Don't log timestamp changes
                        }

                        $oldValue = $existingDBCard[$field] ?? null;

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
                        $this->tablePrefix . 'mtgsource_card',
                        $fieldsToUpdate,
                        ['name_en' => $cardName]
                    );

                    // Update cache with new data
                    $this->existingCardsCache[$cardName] = array_merge($existingDBCard, $fieldsToUpdate);

                    $this->getLogger()->info(
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
                    $this->getLogger()->debug(
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
            'inserted'   => $insertedCount,
            'updated'    => $updatedCount,
            'skipped'    => $skippedCount,
            'prices'     => $pricesCount,
            'errors'     => $errorCount,
            'duel_ranks' => $updatedMTGTop8RanksCount,
        ];
    }

    /**
     * Reset the maximum_timeline_legality for all cards to 'printed' before processing new data.
     * This ensures that the timeline legality is recalculated from scratch for each card.
     *
     * @throws RuntimeException
     *
     * @return void
     */
    private function resetAllCardsMaximumTimelineLegality(): void
    {
        $sql = 'UPDATE ' . $this->tablePrefix . 'mtgsource_card SET maximum_timeline_legality = "printed"';

        try {
            $this->connection->executeStatement($sql);
        } catch (Exception $e) {
            $this->getLogger()->error('Failed to reset maximum_timeline_legality: ' . $e->getMessage());

            throw new RuntimeException('Failed to reset maximum_timeline_legality: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Update duel_rank and cedhrank by fetching MTGTop8 "Duel Commander" and "cEDH decks" popularity page, parsing commander names top-to-bottom,
     * and storing the 1-based index as rank.
     *
     * Matching rule:
     * - compare commander/card name with DB cards using multibyte lowercase (UTF-8)
     *
     * @throws Exception
     *
     * @return int Number of DB rows updated (cards matched + changed)
     */
    private function updateMTGTop8RanksFromCedhMetaPopularity(): int
    {
        // First, Duel Commander
        $duelCommanderMTGTop8URL = 'https://www.mtgtop8.com/cEDH_decks?f=EDH&show=pop&cid=&meta=56&gamerid1=&gamerid2=&cEDH_cp=1';

        $this->getLogger()->info(sprintf('[MTGTOP8] Fetching DC ranks from: %s', $duelCommanderMTGTop8URL));

        $html = $this->fetchHtml($duelCommanderMTGTop8URL);
        $names = $this->parseMTGTop8CommanderNamesFromHtml($html);

        $this->getLogger()->info(sprintf('[MTGTOP8] Parsed commander names: %d', count($names)));

        if (count($names) === 0) {
            $this->getLogger()->warning('[MTGTOP8] No commander names found in MTGTop8 DC HTML.');

            return 0;
        }

        // Build case-insensitive lookup from the cache (must already be loaded by loadExistingCardsCache()).
        $lowerToCanonicalName = [];
        foreach (array_keys($this->existingCardsCache) as $dbName) {
            if ($dbName === '') {
                continue;
            }

            $lowerToCanonicalName[$this->normalizeNameForLookup($dbName)] = $dbName;
        }

        $updated = 0;
        $rank = 0;

        foreach ($names as $commanderName) {
            ++$rank;

            $key = $this->normalizeNameForLookup($commanderName);

            if (! isset($lowerToCanonicalName[$key])) {
                continue;
            }

            $canonicalName = $lowerToCanonicalName[$key];

            // Only update when rank actually changes (avoid unnecessary writes)
            $existing = $this->existingCardsCache[$canonicalName]['duel_rank'] ?? 0;
            $existingInt = is_numeric($existing) ? (int)$existing : 0;

            if ($existingInt === $rank) {
                continue;
            }

            $this->connection->update(
                $this->tablePrefix . 'mtgsource_card',
                ['duel_rank' => $rank],
                ['name_en'   => $canonicalName]
            );

            // Keep cache in sync (helps if this method is called again in the same run)
            $this->existingCardsCache[$canonicalName]['duel_rank'] = $rank;

            ++$updated;
        }

        // Then, CEDH
        $CEDHMTGTop8URL = 'https://www.mtgtop8.com/cEDH_decks?f=cEDH&show=pop&cid=&meta=240&gamerid1=&gamerid2=&cEDH_cp=1';

        $this->getLogger()->info(sprintf('[MTGTOP8] Fetching CEDH ranks from: %s', $CEDHMTGTop8URL));

        $html = $this->fetchHtml($CEDHMTGTop8URL);
        $names = $this->parseMTGTop8CommanderNamesFromHtml($html);

        $this->getLogger()->info(sprintf('[MTGTOP8] Parsed commander names: %d', count($names)));

        if (count($names) === 0) {
            $this->getLogger()->warning('[MTGTOP8] No commander names found in MTGTop8 CEDH HTML.');

            return 0;
        }

        // Build case-insensitive lookup from the cache (must already be loaded by loadExistingCardsCache()).
        $lowerToCanonicalName = [];
        foreach (array_keys($this->existingCardsCache) as $dbName) {
            if ($dbName === '') {
                continue;
            }

            $lowerToCanonicalName[$this->normalizeNameForLookup($dbName)] = $dbName;
        }

        $rank = 0;

        foreach ($names as $commanderName) {
            ++$rank;

            $key = $this->normalizeNameForLookup($commanderName);

            if (! isset($lowerToCanonicalName[$key])) {
                continue;
            }

            $canonicalName = $lowerToCanonicalName[$key];

            // Only update when rank actually changes (avoid unnecessary writes)
            $existing = $this->existingCardsCache[$canonicalName]['cedhrank'] ?? 0;
            $existingInt = is_numeric($existing) ? (int)$existing : 0;

            if ($existingInt === $rank) {
                continue;
            }

            $this->connection->update(
                $this->tablePrefix . 'mtgsource_card',
                ['cedhrank' => $rank],
                ['name_en'  => $canonicalName]
            );

            // Keep cache in sync (helps if this method is called again in the same run)
            $this->existingCardsCache[$canonicalName]['cedhrank'] = $rank;

            ++$updated;
        }

        $this->getLogger()->info(sprintf('[MTGTOP8] Rank update completed. Updated cards: %d', $updated));

        return $updated;
    }
}
