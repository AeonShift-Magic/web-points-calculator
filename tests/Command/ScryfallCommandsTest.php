<?php

declare(strict_types = 1);

namespace App\Tests\Command;

use Override;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @covers \App\Command\MTG\DB\V1\MTGScryfallDefaultCardsSourceDBUpdateCommandV1
 * @covers \App\Command\MTG\Source\V1\MTGScryfallDefaultCardsSourceDownloadCommandV1
 *
 * @internal
 *
 * @small
 */
final class ScryfallCommandsTest extends KernelTestCase
{
    private Application $application;

    /**
     * Test that the ScryfallDefaultCardsSourceDBUpdateCommand dry-run option works
     * and returns correct exit code.
     */
    public function testScryfallDefaultCardsDBUpdateCommandDryRun(): void
    {
        $command = $this->application->find('aeonshift:mtg:updatedb:scryfalldefaultmtgcards:v1');
        $commandTester = new CommandTester($command);

        $commandTester->execute([
            '--dry-run' => true,
        ]);

        $commandTester->assertCommandIsSuccessful();

        $output = $commandTester->getDisplay();
        self::assertStringContainsString('DRY RUN MODE', $output);
    }

    /**
     * Test that the ScryfallDefaultCardsSourceDownloadCommand dry-run option works
     * and returns correct exit code.
     */
    public function testScryfallDefaultCardsDownloadCommandDryRun(): void
    {
        $command = $this->application->find('aeonshift:mtg:sourcedownload:scryfalldefaultmtgcards:v1');
        $commandTester = new CommandTester($command);

        $commandTester->execute([
            '--dry-run' => true,
        ]);

        $commandTester->assertCommandIsSuccessful();

        $output = $commandTester->getDisplay();
        self::assertStringContainsString('DRY RUN MODE', $output);
    }

    /**
     * Test that ScryfallDefaultCardsSourceDBUpdateCommand rejects invalid source options (Dry run, we cannot test the whole command here).
     */
    public function testScryfallDefaultCardsSourceDBUpdateCommandInvalidSource(): void
    {
        $command = $this->application->find('aeonshift:mtg:updatedb:scryfalldefaultmtgcards:v1');
        $commandTester = new CommandTester($command);

        $invalidSources = ['http', 'api', 'invalid', 'test', ''];

        foreach ($invalidSources as $source) {
            $commandTester->execute([
                '--source'  => $source,
                '--dry-run' => true,
            ]);

            self::assertSame(Command::FAILURE, $commandTester->getStatusCode());
            $output = $commandTester->getDisplay();
            self::assertStringContainsString('Invalid source option', $output);
            self::assertStringContainsString('"cli", "web" or "cron"', $output);
        }
    }

    /**
     * Test that ScryfallDefaultCardsSourceDBUpdateCommand accepts valid source options (Dry run, we cannot test the whole command here).
     */
    public function testScryfallDefaultCardsSourceDBUpdateCommandValidSources(): void
    {
        $command = $this->application->find('aeonshift:mtg:updatedb:scryfalldefaultmtgcards:v1');
        $commandTester = new CommandTester($command);

        $validSources = ['cli', 'cron'];

        foreach ($validSources as $source) {
            $commandTester->execute([
                '--source'  => $source,
                '--dry-run' => true,
            ]);

            $commandTester->assertCommandIsSuccessful();
            self::assertNotSame(Command::FAILURE, $commandTester->getStatusCode());
        }
    }

    /**
     * Test that ScryfallDefaultCardsSourceDownloadCommand rejects invalid source options (Dry run, we cannot test the whole command here).
     */
    public function testScryfallDefaultCardsSourceDownloadCommandInvalidSource(): void
    {
        $command = $this->application->find('aeonshift:mtg:sourcedownload:scryfalldefaultmtgcards:v1');
        $commandTester = new CommandTester($command);

        $invalidSources = ['http', 'api', 'invalid', 'test', ''];

        foreach ($invalidSources as $source) {
            $commandTester->execute([
                '--source'  => $source,
                '--dry-run' => true,
            ]);

            self::assertSame(Command::FAILURE, $commandTester->getStatusCode());
            $output = $commandTester->getDisplay();
            self::assertStringContainsString('Invalid source option', $output);
            self::assertStringContainsString('"cli", "web" or "cron"', $output);
        }
    }

    /**
     * Test that ScryfallDefaultCardsSourceDownloadCommand accepts valid source options (Dry run, we cannot test the whole command here).
     */
    public function testScryfallDefaultCardsSourceDownloadCommandValidSources(): void
    {
        $command = $this->application->find('aeonshift:mtg:sourcedownload:scryfalldefaultmtgcards:v1');
        $commandTester = new CommandTester($command);

        $validSources = ['cli', 'cron'];

        foreach ($validSources as $source) {
            $commandTester->execute([
                '--source'  => $source,
                '--dry-run' => true,
            ]);

            $commandTester->assertCommandIsSuccessful();
            self::assertNotSame(Command::FAILURE, $commandTester->getStatusCode());
        }
    }

    #[Override]
    protected function setUp(): void
    {
        self::bootKernel();
        $this->application = new Application(self::$kernel);
    }
}
