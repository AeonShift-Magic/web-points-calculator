<?php

declare(strict_types = 1);

namespace App\Command\MTG\Builder\V1;

use Override;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'aeonshift:mtg:builder:staticassets:v1',
    description: 'Build the static MTG application assets - V1',
    aliases: ['as:mtg:b:sa:v1']
)]
final class MTGCalculatorStaticAssetsBuilderCommandV1 extends Command
{
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

        $io->success('MTG calculator static assets have been generated in /public/static-calculators/mtg');

        return Command::SUCCESS;
    }
}
