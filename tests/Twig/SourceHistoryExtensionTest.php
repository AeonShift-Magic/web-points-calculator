<?php

/** @noinspection DynamicInvocationViaScopeResolutionInspection */

declare(strict_types = 1);

namespace App\Tests\Twig;

use App\Entity\MTG\MTGCardSourceActivityHistory;
use App\Twig\SourceHistoryExtension;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\TwigFunction;

/**
 * @internal
 *
 * @small
 */
final class SourceHistoryExtensionTest extends TestCase
{
    private string $projectDir = '/tmp/project';

    private string $scryfallCardsSourceDir = 'scryfall';

    public function testColorizeChannel(): void
    {
        $urlGenerator = self::createStub(UrlGeneratorInterface::class);
        $extension = new SourceHistoryExtension($this->projectDir, $this->scryfallCardsSourceDir, $urlGenerator);

        $input = 'a/b/c';
        $expected = '<span class="text-indigo-200">a</span> <span class="text-lime-300">b</span> <span class="text-rose-300">c</span> ';
        self::assertSame($expected, $extension->colorizeChannel($input));
    }

    public function testFileLinkOrStringGeneratesLinkIfFileExists(): void
    {
        // Use a stub instead of a mock to avoid the "no expectations" notice
        $urlGenerator = self::createStub(UrlGeneratorInterface::class);
        $urlGenerator->method('generate')->willReturn('generated_url');

        // Create a subclass to override file_exists
        $extension = new class($this->projectDir, $this->scryfallCardsSourceDir, $urlGenerator) extends SourceHistoryExtension {
            protected function fileExists(string $path): bool
            {
                return true; // pretend the file exists
            }
        };

        $history = self::createStub(MTGCardSourceActivityHistory::class);
        $history->id = 123;
        $history->method('getLogFilePath')->willReturn('file.log');
        $history->method('getChannel')->willReturn('scryfall/defaultmtgcards/download');

        $output = $extension->fileLinkOrString($history);

        self::assertStringContainsString('file.log', $output);
    }

    public function testFileLinkOrStringReturnsPlainStringIfConditionsNotMet(): void
    {
        $urlGenerator = self::createStub(UrlGeneratorInterface::class);
        $extension = new SourceHistoryExtension($this->projectDir, $this->scryfallCardsSourceDir, $urlGenerator);

        $history = self::createStub(MTGCardSourceActivityHistory::class);
        $history->method('getLogFilePath')->willReturn('file.log');
        $history->method('getChannel')->willReturn('other/channel');

        self::assertSame('file.log', $extension->fileLinkOrString($history));
    }

    public function testGetFunctionsReturnsExpectedTwigFunctions(): void
    {
        $urlGenerator = self::createStub(UrlGeneratorInterface::class);
        $extension = new SourceHistoryExtension($this->projectDir, $this->scryfallCardsSourceDir, $urlGenerator);

        $functions = $extension->getFunctions();

        self::assertCount(2, $functions);
        self::assertInstanceOf(TwigFunction::class, $functions[0]);
        self::assertSame('colorize_channel', $functions[0]->getName());
        self::assertInstanceOf(TwigFunction::class, $functions[1]);
        self::assertSame('file_link_or_string', $functions[1]->getName());
    }
}
