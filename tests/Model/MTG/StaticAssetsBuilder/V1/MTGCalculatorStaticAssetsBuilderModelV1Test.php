<?php

declare(strict_types = 1);

namespace App\Tests\Model\MTG\StaticAssetsBuilder\V1;

use App\Model\MTG\StaticAssetsBuilder\V1\MTGCalculatorStaticAssetsBuilderModelV1;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @small
 */
final class MTGCalculatorStaticAssetsBuilderModelV1Test extends TestCase
{
    public function testBuildMTGCalculatorStaticAssetsMessageContainsKeyParts(): void
    {
        $model = new MTGCalculatorStaticAssetsBuilderModelV1();

        $result = $model->buildMTGCalculatorStaticAssets();

        self::assertIsString($result);
        self::assertStringContainsString('license MTG', $result);
        self::assertStringContainsString('"public/static-calculators/mtg"', $result);
        self::assertStringContainsString('version 1', $result);
        self::assertStringStartsWith('Static assets successfully built', $result);
        self::assertStringEndsWith('.', $result);
    }

    public function testBuildMTGCalculatorStaticAssetsReturnsExpectedMessage(): void
    {
        $model = new MTGCalculatorStaticAssetsBuilderModelV1();

        $result = $model->buildMTGCalculatorStaticAssets();

        $expected = 'Static assets successfully built for license MTG in "public/static-calculators/mtg" for calculator model version 1.';
        self::assertSame($expected, $result);
    }
}
