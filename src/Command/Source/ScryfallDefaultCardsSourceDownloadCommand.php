<?php

declare(strict_types = 1);

namespace App\Command\Source;

use App\Entity\SourceActivityHistoryInterface;
use App\Model\Source\DownloadModel\MTG\ScryfallDefaultCardsSourceDownloadModel;
use Exception;
use RuntimeException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

#[AsCommand(
    name: 'aeonshift:sourcedownload:scryfalldefaultcards',
    description: 'Download Scryfall default cards bulk data',
    aliases: ['as:sd:sdc']
)]
class ScryfallDefaultCardsSourceDownloadCommand extends Command
{
    public function __construct(
        private readonly ScryfallDefaultCardsSourceDownloadModel $scryfallDownloader,
    )
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

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
                    ['Name', $defaultCardsEntry['name']],
                    ['Updated At', $defaultCardsEntry['updated_at']],
                    ['Size', number_format($defaultCardsEntry['size']) . ' bytes'],
                    ['Download URI', $defaultCardsEntry['download_uri']],
                ]
            );

            $io->section('Downloading default cards data...');
            $io->note('This may take several minutes due to the large file size...');

            $progressBar = $io->createProgressBar($defaultCardsEntry['size']);
            $progressBar->start();

            $downloadedPath = $this->scryfallDownloader->downloadDefaultCards(
                $defaultCardsEntry,
                static function (int $downloadedBytes) use ($progressBar): void {
                    $progressBar->setProgress($downloadedBytes);
                },
                SourceActivityHistoryInterface::SOURCE_CLI
            );

            $progressBar->finish();
            $io->newLine(2);

            $io->success('File downloaded successfully to: ' . $downloadedPath);
            $io->info('File size: ' . number_format(filesize($downloadedPath)) . ' bytes');

            return Command::SUCCESS;
        } catch (TransportExceptionInterface $e) {
            $io->error('HTTP Transport error: ' . $e->getMessage());

            return Command::FAILURE;
        } catch (RuntimeException $e) {
            $io->error('Error: ' . $e->getMessage());

            return Command::FAILURE;
        } catch (Exception $e) {
            $io->error('Unexpected error: ' . $e->getMessage());

            return Command::FAILURE;
        }
    }
}
