<?php

declare(strict_types = 1);

namespace App\Command\DB;

use App\Entity\SourceActivityHistoryInterface;
use App\Model\DBUpdate\DataTransformerModel\MTG\Scryfall\V1\ScryfallDefaultCardsSourceDataTransformerModel;
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

#[AsCommand(
    name: 'aeonshift:updatedb:scryfalldefaultcards',
    description: 'Parse and import latest Scryfall default cards download into database',
    aliases: ['as:udb:sdc']
)]
final class ScryfallDefaultCardsSourceDBUpdateCommand extends Command
{
    /** @var int Error code for lock unavailable */
    private const int ERROR_LOCK_UNAVAILABLE = 2;

    public function __construct(
        private readonly ScryfallDefaultCardsSourceDataTransformerModel $scryfallDefaultCardsSourceDataTransformerModel,
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
            'Display what would be done without actually importing data'
        );

        $this->addOption(
            'source',
            null,
            InputOption::VALUE_OPTIONAL,
            'Specify the source that initiated the import (cli or cron)',
            'cli'
        );
    }

    #[Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $source = $input->getOption('source');

        // Validate the source option
        if (! in_array($source, ['cli', 'cron'], true)) {
            $io->error('Invalid source option. Must be "cli" or "cron".');

            return Command::FAILURE;
        }

        if ($input->getOption('dry-run')) {
            $io->warning('DRY RUN MODE - No data will be imported');

            return Command::SUCCESS;
        }

        try {
            $io->section('Starting Scryfall default cards import...');
            $io->note('This process may take several minutes depending on the number of cards.');

            $progressBar = null;

            $result = $this->scryfallDefaultCardsSourceDataTransformerModel->parseAndImport(
                static function (int $processedCount, int $insertedCount, int $updatedCount, int $skippedCount) use ($io, &$progressBar): void {
                    if ($progressBar === null) {
                        $progressBar = $io->createProgressBar();
                        $progressBar->setFormat(
                            ' %current% cards processed | %elapsed:6s% elapsed | Memory: %memory:6s%' . "\n" .
                            ' Inserted: %inserted% | Updated: %updated% | Skipped: %skipped%'
                        );
                        $progressBar->setMessage((string)$insertedCount, 'inserted');
                        $progressBar->setMessage((string)$updatedCount, 'updated');
                        $progressBar->setMessage((string)$skippedCount, 'skipped');
                        $progressBar->start();
                    }

                    $progressBar->setProgress($processedCount);
                    $progressBar->setMessage((string)$insertedCount, 'inserted');
                    $progressBar->setMessage((string)$updatedCount, 'updated');
                    $progressBar->setMessage((string)$skippedCount, 'skipped');
                },
                $source === 'cron'
                    ? SourceActivityHistoryInterface::SOURCE_CRON
                    : SourceActivityHistoryInterface::SOURCE_CLI
            );

            if ($progressBar !== null) {
                $progressBar->finish();
                $io->newLine(2);
            }

            $io->success('Import completed successfully!');

            $io->table(
                ['Metric', 'Count'],
                [
                    ['Cards Processed', number_format($result['processed'])],
                    ['Cards Inserted', number_format($result['inserted'])],
                    ['Cards Updated', number_format($result['updated'])],
                    ['Cards Skipped (no changes)', number_format($result['skipped'])],
                    ['Errors', number_format($result['errors'])],
                ]
            );

            if ($result['errors'] > 0) {
                $io->warning(sprintf(
                    '%d card(s) failed to import. Check logs for details.',
                    $result['errors']
                ));
            }

            if ($result['skipped'] > 0) {
                $io->info(sprintf(
                    '%d card(s) were skipped because no database update was needed.',
                    $result['skipped']
                ));
            }

            return Command::SUCCESS;

        } catch (LockAcquiringException $e) {
            $io->error('Lock unavailable: ' . $e->getMessage());
            $io->note('The lock expires automatically after 15 minutes. Please wait for the current process to complete or try again later.');

            return self::ERROR_LOCK_UNAVAILABLE;
        } catch (RuntimeException $e) {
            $io->error('Import failed: ' . $e->getMessage());

            return Command::FAILURE;
        } catch (Exception $e) {
            $io->error('Unexpected error: ' . $e->getMessage());

            return Command::FAILURE;
        }
    }
}
