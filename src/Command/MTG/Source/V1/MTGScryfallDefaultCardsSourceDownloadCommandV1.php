<?php

declare(strict_types = 1);

namespace App\Command\MTG\Source\V1;

use App\Entity\SourceActivityHistoryInterface;
use App\Model\MTG\Source\Download\Scryfall\V1\MTGScryfallDefaultCardsSourceDownloadModelV1;
use Exception;
use Override;
use RuntimeException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Lock\Exception\LockAcquiringException;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

/**
 * Starts a download of the Scryfall default cards bulk data file from the CLI.
 */
#[AsCommand(
    name: 'aeonshift:mtg:sourcedownload:scryfalldefaultmtgcards:v1',
    description: 'Download Scryfall default cards bulk data - V1',
    aliases: ['as:mtg:sd:sdc:v1']
)]
final class MTGScryfallDefaultCardsSourceDownloadCommandV1 extends Command
{
    /** @var int Error code for lock unavailable */
    private const int ERROR_LOCK_UNAVAILABLE = 2;

    public function __construct(
        private readonly MTGScryfallDefaultCardsSourceDownloadModelV1 $scryfallDownloader,
    )
    {
        parent::__construct();
    }

    #[Override]
    protected function configure(): void
    {
        $this->addOption(
            'dry-run',
            null,
            InputOption::VALUE_NONE,
            'Display what would be done without actually downloading data'
        );

        $this
            ->addOption(
                'source',
                null,
                InputOption::VALUE_OPTIONAL,
                'Specify the source that initiated the download (cli or cron)',
                'cli'
            );
    }

    #[Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $source = $input->getOption('source');

        // Validate the source option
        if (! in_array($source, ['cli', 'cron', 'web'], true)) {
            $io->error('Invalid source option. Must be "cli", "web" or "cron".');

            return Command::FAILURE;
        }

        if ($input->getOption('dry-run')) {
            $io->warning('DRY RUN MODE - No data will be imported');

            return Command::SUCCESS;
        }

        try {
            $io->section('Fetching Scryfall bulk data information...');

            $bulkData = $this->scryfallDownloader->getBulkDataInfo();
            $defaultCardsEntry = $this->scryfallDownloader->findDefaultCardsEntry($bulkData);

            if ($defaultCardsEntry === null) {
                $io->error('Could not find default_cards entry in bulk data response');

                return Command::FAILURE;
            }

            $io->success('Found default_cards entry');
            $io->table(
                ['Property', 'Value'],
                [
                    ['Name', $defaultCardsEntry['name'] ?? ''],
                    ['Updated At', $defaultCardsEntry['updated_at'] ?? ''],
                    ['Size', number_format(isset($defaultCardsEntry['size']) && is_numeric($defaultCardsEntry['size']) ? (int)$defaultCardsEntry['size'] : 0) . ' bytes'],
                    ['Download URI', $defaultCardsEntry['download_uri'] ?? ''],
                ]
            );

            $io->section('Downloading default cards data...');
            $io->note('This may take several minutes due to the large file size...');

            $progressBar = $io->createProgressBar(isset($defaultCardsEntry['size']) && is_numeric($defaultCardsEntry['size']) ? (int)$defaultCardsEntry['size'] : 0);
            $progressBar->start();

            if ($source === 'cron') {
                $source = SourceActivityHistoryInterface::SOURCE_CRON;
            } elseif ($source === 'web') {
                $source = SourceActivityHistoryInterface::SOURCE_HTTP;
            } else {
                $source = SourceActivityHistoryInterface::SOURCE_CLI;
            }

            $downloadedPath = $this->scryfallDownloader->downloadDefaultCards(
                $defaultCardsEntry,
                static function (int $downloadedBytes) use ($progressBar): void {
                    $progressBar->setProgress($downloadedBytes);
                },
                $source
            );

            $progressBar->finish();
            $io->newLine(2);

            $io->success('File downloaded successfully to: ' . $downloadedPath);
            $io->info('File size: ' . number_format((int)filesize($downloadedPath) ?: 0) . ' bytes');

            return Command::SUCCESS;
        } catch (LockAcquiringException $e) {
            $io->error('Lock unavailable: ' . $e->getMessage());
            $io->note('The lock expires automatically after 15 minutes. Please wait for the current process to complete or try again later.');

            return self::ERROR_LOCK_UNAVAILABLE;
        } catch (RuntimeException $e) {
            $io->error('Error: ' . $e->getMessage());

            return Command::FAILURE;
        } catch (TransportExceptionInterface $e) {
            $io->error('HTTP Transport error: ' . $e->getMessage());

            return Command::FAILURE;
        } catch (Exception $e) {
            $io->error('Unexpected error: ' . $e->getMessage());

            return Command::FAILURE;
        }
    }
}
