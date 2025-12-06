<?php

declare(strict_types = 1);

namespace App\Command\DB;

use App\Entity\SourceActivityHistoryInterface;
use Exception;
use App\Model\DBUpdate\DataTransformerModel\MTG\ScryfallDefaultCardsSourceDataTransformerModel;
use RuntimeException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'aeonshift:updatedb:scryfalldefaultcards',
    description: 'Parse and import latest Scryfall default cards download into database',
    aliases: ['as:udb:sdc']
)]
class ScryfallDefaultCardsSourceDBUpdateCommand extends Command
{
    public function __construct(
        private readonly ScryfallDefaultCardsSourceDataTransformerModel $scryfallDefaultCardsSourceDataTransformerModel,
    )
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption(
            'dry-run',
            null,
            InputOption::VALUE_NONE,
            'Display what would be done without actually importing data'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

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
                SourceActivityHistoryInterface::SOURCE_CLI
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

        } catch (RuntimeException $e) {
            $io->error('Import failed: ' . $e->getMessage());

            return Command::FAILURE;
        } catch (Exception $e) {
            $io->error('Unexpected error: ' . $e->getMessage());

            return Command::FAILURE;
        }
    }
}
