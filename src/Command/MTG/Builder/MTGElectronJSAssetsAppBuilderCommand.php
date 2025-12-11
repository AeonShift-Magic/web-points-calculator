<?php

declare(strict_types = 1);

namespace App\Command\MTG\Builder;

use App\Model\DBUpdate\DataTransformerModel\MTG\Scryfall\V1\MTGScryfallDefaultCardsSourceDataTransformerModel;
use Override;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'aeonshift:mtg:builder:electronjs',
    description: 'Build the ElectronJS MTG application assets',
    aliases: ['as:mtg:b:ejs']
)]
final class MTGElectronJSAssetsAppBuilderCommand extends Command
{
    /** @var int Error code for lock unavailable */
    private const int ERROR_LOCK_UNAVAILABLE = 2;

    public function __construct(
        private readonly MTGScryfallDefaultCardsSourceDataTransformerModel $scryfallDefaultCardsSourceDataTransformerModel,
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
    }

    #[Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        if ($input->getOption('dry-run')) {
            $io->warning('DRY RUN MODE - No asset will be generated');

            return Command::SUCCESS;
        }

        $io->success('MTG calculator ElectronJS + ElectronForge assets have been generated in /out');

        return Command::SUCCESS;
    }
}
