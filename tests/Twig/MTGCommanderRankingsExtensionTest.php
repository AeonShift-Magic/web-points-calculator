<?php

/** @noinspection DynamicInvocationViaScopeResolutionInspection */

declare(strict_types = 1);

namespace App\Tests\Twig;

use App\Twig\MTGCommanderRankingsExtension;
use PHPUnit\Framework\TestCase;
use Twig\TwigFunction;

/**
 * @internal
 *
 * @small
 */
final class MTGCommanderRankingsExtensionTest extends TestCase
{
    public function testGetFunctionsReturnsExpectedTwigFunctions(): void
    {
        $extension = new MTGCommanderRankingsExtension();

        $functions = $extension->getFunctions();

        self::assertCount(2, $functions);
        self::assertInstanceOf(TwigFunction::class, $functions[0]);
        self::assertInstanceOf(TwigFunction::class, $functions[1]);
        self::assertSame('ranking_decorator', $functions[0]->getName());
        self::assertSame('ranking_class', $functions[1]->getName());
    }

    public function testRankingClassReturnsExpectedValues(): void
    {
        $extension = new MTGCommanderRankingsExtension();

        self::assertSame('all', $extension->rankingClass(0));
        self::assertSame('all', $extension->rankingClass(1000, 500));
        self::assertSame('top', $extension->rankingClass(1, 500));
        self::assertSame('popular', $extension->rankingClass(50, 500));
        self::assertSame('alltournamentranked', $extension->rankingClass(300, 500));
    }

    public function testRankingDecoratorGeneratesHtml(): void
    {
        $extension = new MTGCommanderRankingsExtension();

        $html = $extension->rankingDecorator(0);
        self::assertSame('', $html);

        $html = $extension->rankingDecorator(5);
        self::assertStringContainsString('<span title="#5"', $html);
        self::assertStringContainsString('card-ranking', $html);
        self::assertStringContainsString('card-ranking-top', $html);

        $html = $extension->rankingDecorator(50);
        self::assertStringContainsString('card-ranking-popular', $html);
    }
}
