<?php

declare(strict_types = 1);

namespace App\Model\Source\DownloadModel\MTG;

use App\Entity\SourceActivityHistoryInterface;
use App\Model\Source\Factory\SourceActivityHistoryFactory;
use DateMalformedStringException;
use DateTime;
use DateTimeImmutable;
use const DIRECTORY_SEPARATOR;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Monolog\Handler\StreamHandler;
use Monolog\Level;
use Monolog\Logger;
use const PHP_URL_PATH;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class ScryfallDefaultCardsSourceDownloadModel
{
    private const string CHANNEL = 'scryfall/defaultcards/download';

    private const int DIR_PERMISSIONS = 0775;

    private const int DOWNLOAD_TIMEOUT = 1800;

    private const int FILE_PERMISSIONS = 0664;

    private const string LICENSE = 'MTG';

    private ?LoggerInterface $logger = null;

    private SourceActivityHistoryInterface $sourceActivityHistory;

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly EntityManagerInterface $entityManager,
        private readonly string $projectDir,
        private readonly string $scryfallBulkApiUrl,
        private readonly string $cardsSourceDir,
        private readonly string $bulkDataType,
        private readonly SourceActivityHistoryFactory $sourceActivityHistoryFactory,
    )
    {
        $this->sourceActivityHistory = $this->sourceActivityHistoryFactory->create(self::LICENSE);
    }

    /**
     * Downloads the default cards file from Scryfall.
     *
     * @param array<string, mixed> $defaultCardsEntry
     * @param callable|null $progressCallback Callback function: function(int $downloadedBytes): void
     *
     * @throws RuntimeException
     * @throws DateMalformedStringException|TransportExceptionInterface
     *
     * @return string Path to the downloaded file
     */
    public function downloadDefaultCards(array $defaultCardsEntry, ?callable $progressCallback = null, string $startedFrom = 'cli'): string
    {
        $startTime = new DateTimeImmutable();

        try {
            $cardsSourcePath = $this->ensureCardsSourceDirectory();
            $this->cleanCardsSourceDirectory($cardsSourcePath);

            if (! isset($defaultCardsEntry['download_uri']) || ! is_string($defaultCardsEntry['download_uri'])) {
                throw new RuntimeException('The default cards entry is missing the download_uri field.');
            }

            $downloadUri = $defaultCardsEntry['download_uri'];
            $destinationPath = $this->buildDestinationPath($cardsSourcePath, $downloadUri);

            // Initialize logger with the destination file name
            $logFileName = $this->initializeLogger($destinationPath);

            // Initialize the table entry for this source activity history
            $this->initializeDatabaseSourceActivityHistory($startTime, $startedFrom, $logFileName);

            $this->validateDefaultCardsEntry($defaultCardsEntry);

            $this->getLogger()->info('=== SCRYFALL DEFAULT CARDS DOWNLOAD STARTED ===');
            $this->getLogger()->info('Start time: {time}', ['time' => $startTime->format('Y-m-d H:i:s')]);
            $this->getLogger()->info('Entry validation passed');
            $this->getLogger()->info('Entry details', [
                'name'          => $defaultCardsEntry['name'] ?? 'N/A',
                'updated_at'    => $defaultCardsEntry['updated_at'] ?? 'N/A',
                'expected_size' => $defaultCardsEntry['size'] ?? 0,
                'download_uri'  => $defaultCardsEntry['download_uri'],
            ]);
            $this->getLogger()->info('Cards source directory: {path}', ['path' => $cardsSourcePath]);
            $this->getLogger()->info('Destination path: {path}', ['path' => $destinationPath]);

            $lastLoggedBytes = 0;
            $logInterval = (isset($defaultCardsEntry['size']) && is_numeric($defaultCardsEntry['size'])) ? (int)$defaultCardsEntry['size'] / 20 : 1; // Log every 5%

            $this->performDownload(
                $downloadUri,
                $destinationPath,
                function (int $downloadedBytes) use ($progressCallback, $defaultCardsEntry, &$lastLoggedBytes, $logInterval): void {
                    if ($downloadedBytes - $lastLoggedBytes >= $logInterval) {
                        $percentage = ($downloadedBytes / ((isset($defaultCardsEntry['size']) && is_numeric($defaultCardsEntry['size'])) ? (int)$defaultCardsEntry['size'] : 100)) * 100;
                        $this->getLogger()->info('Download progress: {downloaded} / {total} bytes ({percentage}%)', [
                            'downloaded' => $downloadedBytes,
                            'total'      => ((isset($defaultCardsEntry['size']) && is_numeric($defaultCardsEntry['size'])) ? (int)$defaultCardsEntry['size'] : 100),
                            'percentage' => round($percentage, 1),
                        ]);
                        $lastLoggedBytes = $downloadedBytes;
                    }

                    if ($progressCallback !== null) {
                        $progressCallback($downloadedBytes);
                    }
                }
            );

            $this->setFilePermissions($destinationPath);
            $this->getLogger()->info('File permissions set');

            $finalSize = filesize($destinationPath);
            $endTime = new DateTimeImmutable();
            $duration = $endTime->getTimestamp() - $startTime->getTimestamp();

            // Finalize source activity history in both logs and DB
            $this->sourceActivityHistory->setEndedAt(new DateTime());
            $this->entityManager->persist($this->sourceActivityHistory);
            $this->entityManager->flush();

            $this->getLogger()->info('=== DOWNLOAD COMPLETED SUCCESSFULLY ===');
            $this->getLogger()->info('Download summary', [
                'downloaded_file'                => $destinationPath,
                'final_size'                     => $finalSize,
                'end_time'                       => $endTime->format('Y-m-d H:i:s'),
                'duration_seconds'               => $duration,
                'duration_minutes'               => round($duration / 60, 2),
                'average_speed_bytes_per_second' => $finalSize / max($duration, 1),
            ]);

            return $destinationPath;

        } catch (Exception $e) {
            if ($this->getLogger() !== null) {
                // Finalize source activity history in both logs and DB
                $this->sourceActivityHistory->setEndedAt(new DateTime());
                $this->entityManager->persist($this->sourceActivityHistory);
                $this->entityManager->flush();

                $this->getLogger()->error('=== ERROR OCCURRED ===');
                $this->getLogger()->error('Exception: {class}', ['class' => get_class($e)]);
                $this->getLogger()->error('Message: {message}', ['message' => $e->getMessage()]);
                $this->getLogger()->error('Code: {code}', ['code' => $e->getCode()]);
                $this->getLogger()->error('File: {file}:{line}', ['file' => $e->getFile(), 'line' => $e->getLine()]);
                $this->getLogger()->debug('Stack trace', ['trace' => $e->getTraceAsString()]);
            }

            throw $e;
        }
    }

    /**
     * Complete workflow: fetch info, find default cards, and download.
     *
     * @param callable|null $progressCallback Callback function to track progress
     *
     * @throws RuntimeException
     * @throws TransportExceptionInterface
     * @throws DateMalformedStringException|DecodingExceptionInterface
     *
     * @return array{path: string, entry: array<string, mixed>}
     */
    public function downloadLatestDefaultCards(?callable $progressCallback = null): array
    {
        $bulkData = $this->getBulkDataInfo();
        $defaultCardsEntry = $this->findDefaultCardsEntry($bulkData);

        if ($defaultCardsEntry === null) {
            throw new RuntimeException(sprintf('Could not find "%s" entry in bulk data response', $this->bulkDataType));
        }

        $path = $this->downloadDefaultCards($defaultCardsEntry, $progressCallback);

        return [
            'path'  => $path,
            'entry' => $defaultCardsEntry,
        ];
    }

    /**
     * @param array<string, mixed> $bulkData
     *
     * @return array<string, mixed>|null
     */
    public function findDefaultCardsEntry(array $bulkData): ?array
    {
        if (! isset($bulkData['data']) || ! is_array($bulkData['data'])) {
            return null;
        }

        return array_find($bulkData['data'], fn (array $entry) => isset($entry['type']) && $entry['type'] === $this->bulkDataType);
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     *
     * @return array<string, mixed>
     */
    public function getBulkDataInfo(): array
    {
        return $this->httpClient->request('GET', $this->scryfallBulkApiUrl)->toArray(); // @phpstan-ignore-line
    }

    private function buildDestinationPath(string $cardsSourcePath, string $downloadUri): string
    {
        $parsedUrl = parse_url($downloadUri, PHP_URL_PATH);

        if ($parsedUrl === null || $parsedUrl === false) {
            throw new RuntimeException(sprintf('Invalid download URI: %s', $downloadUri));
        }

        $filename = basename($parsedUrl);

        if (empty($filename)) {
            throw new RuntimeException(sprintf('Could not extract filename from URI: %s', $downloadUri));
        }

        return $cardsSourcePath . DIRECTORY_SEPARATOR . $filename;
    }

    private function cleanCardsSourceDirectory(string $cardsSourcePath): void
    {
        $files = glob($cardsSourcePath . DIRECTORY_SEPARATOR . '*');

        if ($files === false) {
            throw new RuntimeException(sprintf('Failed to list files in directory: %s', $cardsSourcePath));
        }

        $deletedCount = 0;
        foreach ($files as $file) {
            if (is_file($file) && ! unlink($file)) {
                throw new RuntimeException(sprintf('Failed to delete file: %s', $file));
            }
            ++$deletedCount;
        }

        if ($deletedCount > 0 && $this->getLogger() !== null) {
            $this->getLogger()->info('Deleted {count} old file(s) from directory', ['count' => $deletedCount]);
        }
    }

    /**
     * @param resource $fileHandle
     * @param string $destinationPath
     *
     * @return void
     */
    private function cleanupFailedDownload($fileHandle, string $destinationPath): void
    {
        if (is_resource($fileHandle)) {
            fclose($fileHandle);
        }

        if (file_exists($destinationPath)) {
            @unlink($destinationPath);
            $this->getLogger()?->warning('Cleaned up partial download file');
        }
    }

    private function ensureCardsSourceDirectory(): string
    {
        $cardsSourcePath = $this->projectDir . DIRECTORY_SEPARATOR . $this->cardsSourceDir;

        if (! is_dir($cardsSourcePath) && ! mkdir($cardsSourcePath, self::DIR_PERMISSIONS, true) && ! is_dir($cardsSourcePath)) {
            throw new RuntimeException(sprintf('Failed to create directory: %s', $cardsSourcePath));
        }

        return $cardsSourcePath;
    }

    /**
     * @return LoggerInterface
     */
    private function getLogger(): LoggerInterface
    {
        if ($this->logger === null) {
            throw new RuntimeException('Logger not initialized');
        }

        return $this->logger;
    }

    /**
     * This method initialized the database table for MTG Source Activity History.
     *
     * What it does is it takes the unique source identifier for this import session
     * and creates an entry in the database table for the matching SourceActivityHistoryInterface
     * to track this import session and prepares the line.
     *
     * @param DateTimeImmutable $currentDate
     * @param string $startedFrom
     * @param string $logFileName
     *
     * @return void
     */
    private function initializeDatabaseSourceActivityHistory(DateTimeImmutable $currentDate, string $startedFrom, string $logFileName): void
    {
        $this->sourceActivityHistory
            ->setChannel(static::CHANNEL)
            ->setStartedAt(DateTime::createFromImmutable($currentDate))
            ->setStartedFrom($startedFrom)
            ->setLogFilePath($logFileName);

        $this->entityManager->persist($this->sourceActivityHistory);
        $this->entityManager->flush();
    }

    private function initializeLogger(string $destinationPath): string
    {
        /** @var array{dirname: string, basename: string, extension: string, filename: string} $pathInfo */
        $pathInfo = pathinfo($destinationPath);
        $directory = $pathInfo['dirname'];

        // Delete all existing .download.log files in the directory
        $existingLogFiles = glob($directory . DIRECTORY_SEPARATOR . '*.download.log');
        if ($existingLogFiles !== false) {
            foreach ($existingLogFiles as $logFile) {
                if (is_file($logFile)) {
                    @unlink($logFile);
                }
            }
        }

        $logFileName = $pathInfo['filename'] . '.download.log';
        $logFilePath = $directory . DIRECTORY_SEPARATOR . $logFileName;

        $this->logger = new Logger('scryfall_download');
        $this->logger->pushHandler(new StreamHandler($logFilePath, Level::Debug));

        return $logFileName;
    }

    private function performDownload(string $downloadUri, string $destinationPath, ?callable $progressCallback): void
    {
        if ($this->getLogger() === null) {
            throw new RuntimeException('Logger initialization failed');
        }

        $fileHandle = fopen($destinationPath, 'wb');

        if ($fileHandle === false) {
            throw new RuntimeException(sprintf('Failed to open destination file for writing: %s', $destinationPath));
        }

        try {
            $this->getLogger()->info('Starting download from: {uri}', ['uri' => $downloadUri]);

            $response = $this->httpClient->request('GET', $downloadUri, [
                'timeout' => self::DOWNLOAD_TIMEOUT,
            ]);

            $downloadedBytes = 0;

            foreach ($this->httpClient->stream($response) as $chunk) {
                $content = $chunk->getContent();
                $bytesWritten = fwrite($fileHandle, $content);

                if ($bytesWritten === false) {
                    throw new RuntimeException('Failed to write to destination file');
                }

                $downloadedBytes += $bytesWritten;

                if ($progressCallback !== null) {
                    $progressCallback($downloadedBytes);
                }
            }

            $this->getLogger()->info('Total bytes downloaded: {bytes}', ['bytes' => $downloadedBytes]);

        } catch (Exception $e) {
            $this->cleanupFailedDownload($fileHandle, $destinationPath);

            throw $e;
        }

        fclose($fileHandle);
    }

    private function setFilePermissions(string $filePath): void
    {
        if (! chmod($filePath, self::FILE_PERMISSIONS)) {
            throw new RuntimeException(sprintf('Failed to set permissions on file: %s', $filePath));
        }
    }

    /**
     * @param array<string, mixed> $entry
     *
     * @return void
     */
    private function validateDefaultCardsEntry(array $entry): void
    {
        $requiredFields = ['download_uri', 'size'];

        foreach ($requiredFields as $field) {
            if (! isset($entry[$field])) {
                throw new RuntimeException(sprintf('Missing required field "%s" in default cards entry', $field));
            }
        }
    }
}
