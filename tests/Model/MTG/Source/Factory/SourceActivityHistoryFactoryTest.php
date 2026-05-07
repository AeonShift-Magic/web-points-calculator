<?php

/** @noinspection ALL */

declare(strict_types = 1);

namespace App\Tests\Model\MTG\Source\Factory;

use App\Entity\MTG\MTGCardSourceActivityHistory;
use App\Entity\SourceActivityHistoryInterface;
use App\Model\MTG\Source\Factory\SourceActivityHistoryFactory;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @small
 */
final class SourceActivityHistoryFactoryTest extends TestCase
{
    public static function unsupportedLicenseProvider(): array
    {
        return [
            'lowercase mtg' => ['mtg'],
            'empty string'  => [''],
            'random value'  => ['XYZ'],
            'whitespace'    => [' '],
        ];
    }

    public function testCreateReturnsMTGCardSourceActivityHistoryForMTGLicense(): void
    {
        $factory = new SourceActivityHistoryFactory();

        $result = $factory->create('MTG');

        self::assertInstanceOf(MTGCardSourceActivityHistory::class, $result);
        self::assertInstanceOf(SourceActivityHistoryInterface::class, $result);
    }

    #[DataProvider('unsupportedLicenseProvider')]
    public function testCreateThrowsForUnsupportedLicense(string $license): void
    {
        $factory = new SourceActivityHistoryFactory();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Unsupported license: {$license}");

        $factory->create($license);
    }
}
