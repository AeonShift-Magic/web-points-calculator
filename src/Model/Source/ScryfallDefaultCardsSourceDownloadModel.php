<?php

declare(strict_types = 1);

namespace App\Model\Source;

use DateMalformedStringException;
use DateTimeImmutable;
use const DIRECTORY_SEPARATOR;
use Exception;
use Monolog\Handler\StreamHandler;
use Monolog\Level;
use Monolog\Logger;
use const PHP_URL_PATH;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class ScryfallDefaultCardsSourceDownloadModel
{
    private const int DIR_PERMISSIONS = 0775;

    private const int DOWNLOAD_TIMEOUT = 1800;

    private const int FILE_PERMISSIONS = 0664;

    private ?LoggerInterface $logger = null;

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly string $projectDir,
        private readonly string $scryfallBulkApiUrl,
        private readonly string $cardsSourceDir,
        private readonly string $bulkDataType,
    ) {
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
    public function downloadDefaultCards(array $defaultCardsEntry, ?callable $progressCallback = null): string
    {
        $startTime = new DateTimeImmutable();

        try {
            $this->validateDefaultCardsEntry($defaultCardsEntry);

            $cardsSourcePath = $this->ensureCardsSourceDirectory();
            $this->cleanCardsSourceDirectory($cardsSourcePath);

            $downloadUri = $defaultCardsEntry['download_uri'];
            $destinationPath = $this->buildDestinationPath($cardsSourcePath, $downloadUri);

            // Initialize logger with the destination file name
            $this->initializeLogger($destinationPath);

            $this->logger->info('=== SCRYFALL DEFAULT CARDS DOWNLOAD STARTED ===');
            $this->logger->info('Start time: {time}', ['time' => $startTime->format('Y-m-d H:i:s')]);
            $this->logger->info('Entry validation passed');
            $this->logger->info('Entry details', [
                'name'          => $defaultCardsEntry['name'] ?? 'N/A',
                'updated_at'    => $defaultCardsEntry['updated_at'] ?? 'N/A',
                'expected_size' => $defaultCardsEntry['size'],
                'download_uri'  => $defaultCardsEntry['download_uri'],
            ]);
            $this->logger->info('Cards source directory: {path}', ['path' => $cardsSourcePath]);
            $this->logger->info('Destination path: {path}', ['path' => $destinationPath]);

            $lastLoggedBytes = 0;
            $logInterval = $defaultCardsEntry['size'] / 20; // Log every 5%

            $this->performDownload(
                $downloadUri,
                $destinationPath,
                function (int $downloadedBytes) use ($progressCallback, $defaultCardsEntry, &$lastLoggedBytes, $logInterval): void {
                    if ($downloadedBytes - $lastLoggedBytes >= $logInterval) {
                        $percentage = ($downloadedBytes / $defaultCardsEntry['size']) * 100;
                        $this->logger->info('Download progress: {downloaded} / {total} bytes ({percentage}%)', [
                            'downloaded' => $downloadedBytes,
                            'total'      => $defaultCardsEntry['size'],
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
            $this->logger->info('File permissions set');

            $finalSize = filesize($destinationPath);
            $endTime = new DateTimeImmutable();
            $duration = $endTime->getTimestamp() - $startTime->getTimestamp();

            $this->logger->info('=== DOWNLOAD COMPLETED SUCCESSFULLY ===');
            $this->logger->info('Download summary', [
                'downloaded_file'                => $destinationPath,
                'final_size'                     => $finalSize,
                'end_time'                       => $endTime->format('Y-m-d H:i:s'),
                'duration_seconds'               => $duration,
                'duration_minutes'               => round($duration / 60, 2),
                'average_speed_bytes_per_second' => $finalSize / max($duration, 1),
            ]);

            return $destinationPath;

        } catch (Exception $e) {
            if ($this->logger !== null) {
                $this->logger->error('=== ERROR OCCURRED ===');
                $this->logger->error('Exception: {class}', ['class' => get_class($e)]);
                $this->logger->error('Message: {message}', ['message' => $e->getMessage()]);
                $this->logger->error('Code: {code}', ['code' => $e->getCode()]);
                $this->logger->error('File: {file}:{line}', ['file' => $e->getFile(), 'line' => $e->getLine()]);
                $this->logger->debug('Stack trace', ['trace' => $e->getTraceAsString()]);
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
     * @throws DateMalformedStringException
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

    public function findDefaultCardsEntry(array $bulkData): ?array
    {
        if (! isset($bulkData['data']) || ! is_array($bulkData['data'])) {
            return null;
        }

        foreach ($bulkData['data'] as $entry) {
            if (isset($entry['type']) && $entry['type'] === $this->bulkDataType) {
                return $entry;
            }
        }

        return null;
    }

    public function getBulkDataInfo(): array
    {
        return $this->httpClient->request('GET', $this->scryfallBulkApiUrl)->toArray();
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

        if ($deletedCount > 0 && $this->logger !== null) {
            $this->logger->info('Deleted {count} old file(s) from directory', ['count' => $deletedCount]);
        }
    }

    private function cleanupFailedDownload($fileHandle, string $destinationPath): void
    {
        if (is_resource($fileHandle)) {
            fclose($fileHandle);
        }

        if (file_exists($destinationPath)) {
            @unlink($destinationPath);
            $this->logger?->warning('Cleaned up partial download file');
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

    private function initializeLogger(string $destinationPath): void
    {
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
    }

    private function performDownload(string $downloadUri, string $destinationPath, ?callable $progressCallback): void
    {
        $fileHandle = fopen($destinationPath, 'wb');

        if ($fileHandle === false) {
            throw new RuntimeException(sprintf('Failed to open destination file for writing: %s', $destinationPath));
        }

        try {
            $this->logger->info('Starting download from: {uri}', ['uri' => $downloadUri]);

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

            $this->logger->info('Total bytes downloaded: {bytes}', ['bytes' => $downloadedBytes]);

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
