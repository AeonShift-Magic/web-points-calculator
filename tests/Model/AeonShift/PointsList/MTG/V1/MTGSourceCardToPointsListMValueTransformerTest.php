<?php

declare(strict_types = 1);

namespace App\Tests\Model\AeonShift\PointsList\MTG\V1;

use App\Entity\MTG\MTGPointsList;
use App\Entity\MTG\MTGSourceCard;
use App\Model\AeonShift\PointsList\MTG\V1\MTGSourceCardToPointsListMValueTransformer;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @small
 */
final class MTGSourceCardToPointsListMValueTransformerTest extends TestCase
{
    public function testFromMTGSourceCardAlwaysInitializesValuePointsToZero(): void
    {
        // Arrange
        $sourceCard = self::createStub(MTGSourceCard::class);
        $pointsList = self::createStub(MTGPointsList::class);

        $sourceCard->method('getNameEN')->willReturn('Test Card');
        $sourceCard->method('getMTGOPrice')->willReturn('100.00');
        $sourceCard->method('getMValueTrend')->willReturn('150.00');

        // Act
        $result = MTGSourceCardToPointsListMValueTransformer::fromMTGSourceCard($sourceCard, $pointsList);

        // Assert - ValuePoints should always be initialized to 0.0 regardless of prices
        self::assertSame(0.0, $result->getValuePoints());
        self::assertIsFloat($result->getValuePoints());
    }

    public function testFromMTGSourceCardCreatesNewInstanceEachTime(): void
    {
        // Arrange
        $sourceCard = self::createStub(MTGSourceCard::class);
        $pointsList = self::createStub(MTGPointsList::class);

        $sourceCard->method('getNameEN')->willReturn('Sol Ring');
        $sourceCard->method('getMTGOPrice')->willReturn('5.00');
        $sourceCard->method('getMValueTrend')->willReturn('6.00');

        // Act
        $result1 = MTGSourceCardToPointsListMValueTransformer::fromMTGSourceCard($sourceCard, $pointsList);
        $result2 = MTGSourceCardToPointsListMValueTransformer::fromMTGSourceCard($sourceCard, $pointsList);

        // Assert - Each call should create a new instance
        self::assertNotSame($result1, $result2);
        self::assertSame($result1->getNameEN(), $result2->getNameEN());
        self::assertSame($result1->getMTGOPrice(), $result2->getMTGOPrice());
        self::assertSame($result1->getMValueTrend(), $result2->getMValueTrend());
    }

    public function testFromMTGSourceCardTransformsCorrectly(): void
    {
        // Arrange
        $sourceCard = self::createStub(MTGSourceCard::class);
        $pointsList = self::createStub(MTGPointsList::class);

        $sourceCard->method('getNameEN')->willReturn('Lightning Bolt');
        $sourceCard->method('getMTGOPrice')->willReturn('2.50');
        $sourceCard->method('getMValueTrend')->willReturn('3.75');

        // Act
        $result = MTGSourceCardToPointsListMValueTransformer::fromMTGSourceCard($sourceCard, $pointsList);

        // Assert
        self::assertSame('Lightning Bolt', $result->getNameEN());
        self::assertSame('2.50', $result->getMTGOPrice());
        self::assertSame('3.75', $result->getMValueTrend());
        self::assertSame($pointsList, $result->getPointsList());
        self::assertSame(0.0, $result->getValuePoints());
    }

    public function testFromMTGSourceCardWithEmptyStrings(): void
    {
        // Arrange
        $sourceCard = self::createStub(MTGSourceCard::class);
        $pointsList = self::createStub(MTGPointsList::class);

        $sourceCard->method('getNameEN')->willReturn('');
        $sourceCard->method('getMTGOPrice')->willReturn('');
        $sourceCard->method('getMValueTrend')->willReturn('');

        // Act
        $result = MTGSourceCardToPointsListMValueTransformer::fromMTGSourceCard($sourceCard, $pointsList);

        // Assert
        self::assertSame('', $result->getNameEN());
        self::assertSame('', $result->getMTGOPrice());
        self::assertSame('', $result->getMValueTrend());
        self::assertSame(0.0, $result->getValuePoints());
    }

    public function testFromMTGSourceCardWithHighPrices(): void
    {
        // Arrange
        $sourceCard = self::createStub(MTGSourceCard::class);
        $pointsList = self::createStub(MTGPointsList::class);

        $sourceCard->method('getNameEN')->willReturn('Mox Sapphire');
        $sourceCard->method('getMTGOPrice')->willReturn('999.99');
        $sourceCard->method('getMValueTrend')->willReturn('1500.50');

        // Act
        $result = MTGSourceCardToPointsListMValueTransformer::fromMTGSourceCard($sourceCard, $pointsList);

        // Assert
        self::assertSame('Mox Sapphire', $result->getNameEN());
        self::assertSame('999.99', $result->getMTGOPrice());
        self::assertSame('1500.50', $result->getMValueTrend());
        self::assertSame($pointsList, $result->getPointsList());
        self::assertSame(0.0, $result->getValuePoints());
    }

    public function testFromMTGSourceCardWithSpecialCharactersInName(): void
    {
        // Arrange
        $sourceCard = self::createStub(MTGSourceCard::class);
        $pointsList = self::createStub(MTGPointsList::class);

        $sourceCard->method('getNameEN')->willReturn("Ral's Outburst // Ral, Storm Conduit");
        $sourceCard->method('getMTGOPrice')->willReturn('1.25');
        $sourceCard->method('getMValueTrend')->willReturn('2.00');

        // Act
        $result = MTGSourceCardToPointsListMValueTransformer::fromMTGSourceCard($sourceCard, $pointsList);

        // Assert
        self::assertSame("Ral's Outburst // Ral, Storm Conduit", $result->getNameEN());
        self::assertSame('1.25', $result->getMTGOPrice());
        self::assertSame('2.00', $result->getMValueTrend());
    }

    public function testFromMTGSourceCardWithZeroPrices(): void
    {
        // Arrange
        $sourceCard = self::createStub(MTGSourceCard::class);
        $pointsList = self::createStub(MTGPointsList::class);

        $sourceCard->method('getNameEN')->willReturn('Black Lotus');
        $sourceCard->method('getMTGOPrice')->willReturn('0.00');
        $sourceCard->method('getMValueTrend')->willReturn('0.00');

        // Act
        $result = MTGSourceCardToPointsListMValueTransformer::fromMTGSourceCard($sourceCard, $pointsList);

        // Assert
        self::assertSame('Black Lotus', $result->getNameEN());
        self::assertSame('0.00', $result->getMTGOPrice());
        self::assertSame('0.00', $result->getMValueTrend());
        self::assertSame($pointsList, $result->getPointsList());
        self::assertSame(0.0, $result->getValuePoints());
    }
}
