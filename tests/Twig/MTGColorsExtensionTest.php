<?php

/** @noinspection DynamicInvocationViaScopeResolutionInspection */

declare(strict_types = 1);

namespace App\Tests\Twig;

use App\Twig\MTGColorsExtension;
use PHPUnit\Framework\TestCase;
use Twig\TwigFunction;

/**
 * @internal
 *
 * @small
 */
final class MTGColorsExtensionTest extends TestCase
{
    public function testColorIdentityArrayToBallsReturnsExpectedHtml(): void
    {
        $extension = new MTGColorsExtension();

        $colors = ['W', 'U', 'B', 'R', 'G'];

        $expected = '';
        foreach ($colors as $c) {
            $expected .= '<span title="{' . $c . '}" class="color-identity color-identity-' . mb_strtolower($c) . '">{' . $c . '}</span>';
        }

        $output = $extension->colorIdentityArrayToBalls($colors);

        self::assertSame($expected, $output);
    }

    public function testGetFunctionsReturnsExpectedTwigFunction(): void
    {
        $extension = new MTGColorsExtension();

        $functions = $extension->getFunctions();

        self::assertCount(1, $functions);
        self::assertInstanceOf(TwigFunction::class, $functions[0]);
        self::assertSame('color_identity_array_to_balls', $functions[0]->getName());
    }
}
