<?php

declare(strict_types = 1);

namespace App\Tests\Entity\MTG;

use App\Entity\MTG\MTGAbstractCard;
use PHPUnit\Framework\TestCase;

/**
 * A simple concrete implementation to be able to instantiate MTGAbstractCard.
 */
final class DummyMTGCard extends MTGAbstractCard
{
    // No additional code needed; MTGAbstractCard implements all required interface methods.
}

/**
 * @internal
 *
 * @small
 */
final class MTGAbstractCardTest extends TestCase
{
    public function testDefaultCalculatorPointsAreZeroWithCorrectFallbacks(): void
    {
        $card = new DummyMTGCard();

        $points = $card->getCalculatorPointsAsArray();

        // Bases
        self::assertSame(0.0, $points['basequadruples']);
        self::assertSame(0.0, $points['basesingletonrules']);

        // Singleton-based fallbacks
        self::assertSame($points['basesingletonrules'], $points['highlander']);
        self::assertSame($points['basesingletonrules'], $points['duelcommander']);
        self::assertSame($points['duelcommander'], $points['duelcommanderspecial']);
        self::assertSame($points['basesingletonrules'], $points['commander']);
        self::assertSame($points['commander'], $points['commanderspecial']);

        // Quadruples-based fallbacks
        self::assertSame($points['basequadruples'], $points['2hg']);
        self::assertSame($points['2hg'], $points['2hgspecial']);
        self::assertSame($points['basequadruples'], $points['modern']);
        self::assertSame($points['basequadruples'], $points['pioneer']);
        self::assertSame($points['basequadruples'], $points['standard']);
    }

    public function testFluentSettersReturnSelf(): void
    {
        $card = new DummyMTGCard();

        self::assertSame($card, $card->setAlternateNameEN('Alt'));
        self::assertSame($card, $card->setFlavorOfNameEN('Flavor'));
        self::assertSame($card, $card->setIsLegal2HG(true));
        self::assertSame($card, $card->setIsLegal2HGSpecial(true));
        self::assertSame($card, $card->setIsLegalCommander(true));
        self::assertSame($card, $card->setIsLegalCommanderSpecial(true));
        self::assertSame($card, $card->setIsLegalDuelCommander(true));
        self::assertSame($card, $card->setIsLegalDuelCommanderSpecial(true));
        self::assertSame($card, $card->setManaValue(5.0));
        self::assertSame($card, $card->setMultiCZType('partner_type_choose_a_background'));
        self::assertSame($card, $card->setNameEN('Card Name'));
        self::assertSame($card, $card->setOracleId('123e4567-e89b-12d3-a456-426614174000'));
        self::assertSame($card, $card->setPoints2HG(1.0));
        self::assertSame($card, $card->setPoints2HGSpecial(2.0));
        self::assertSame($card, $card->setPointsBaseQuadruples(3.0));
        self::assertSame($card, $card->setPointsBaseSingleton(4.0));
        self::assertSame($card, $card->setPointsCommander(5.0));
        self::assertSame($card, $card->setPointsCommanderSpecial(6.0));
        self::assertSame($card, $card->setPointsDuelCommander(7.0));
        self::assertSame($card, $card->setPointsDuelCommanderSpecial(8.0));
        self::assertSame($card, $card->setPointsHighlander(9.0));
        self::assertSame($card, $card->setPointsModern(10.0));
        self::assertSame($card, $card->setPointsPioneer(11.0));
        self::assertSame($card, $card->setPointsStandard(12.0));
        self::assertSame($card, $card->setScryfallId('123e4567-e89b-12d3-a456-426614174001'));
        self::assertSame($card, $card->setScryfallURI('https://scryfall.com/card/xxx'));
    }

    public function testLegalitiesSettersAndGetters(): void
    {
        $card = new DummyMTGCard()
            ->setIsLegal2HG(true)
            ->setIsLegal2HGSpecial(false)
            ->setIsLegalCommander(true)
            ->setIsLegalCommanderSpecial(false)
            ->setIsLegalDuelCommander(true)
            ->setIsLegalDuelCommanderSpecial(false);

        self::assertTrue($card->isLegal2HG());
        self::assertFalse($card->isLegal2HGSpecial());
        self::assertTrue($card->isLegalCommander());
        self::assertFalse($card->isLegalCommanderSpecial());
        self::assertTrue($card->isLegalDuelCommander());
        self::assertFalse($card->isLegalDuelCommanderSpecial());
    }

    public function testQuadruplesPrecedenceAndSpecialFallbacks(): void
    {
        $card = new DummyMTGCard()
            ->setPointsBaseQuadruples(2.0) // basequadruples
            ->setPoints2HG(null)           // fallback to basequadruples
            ->setPoints2HGSpecial(null)    // fallback to 2hg
            ->setPointsModern(null)        // fallback to basequadruples
            ->setPointsPioneer(null)       // fallback to basequadruples
            ->setPointsStandard(null);     // fallback to basequadruples

        $points = $card->getCalculatorPointsAsArray();

        self::assertSame(2.0, $points['basequadruples']);
        self::assertSame(2.0, $points['2hg']);
        self::assertSame(2.0, $points['2hgspecial']);
        self::assertSame(2.0, $points['modern']);
        self::assertSame(2.0, $points['pioneer']);
        self::assertSame(2.0, $points['standard']);
    }

    public function testQuadruplesSpecialOverridesFallback(): void
    {
        $card = new DummyMTGCard()
            ->setPointsBaseQuadruples(1.0)
            ->setPoints2HG(3.0)
            ->setPoints2HGSpecial(4.0)
            ->setPointsModern(5.0)
            ->setPointsPioneer(6.0)
            ->setPointsStandard(7.0);

        $points = $card->getCalculatorPointsAsArray();

        self::assertSame(3.0, $points['2hg']);
        self::assertSame(4.0, $points['2hgspecial']); // explicit special
        self::assertSame(5.0, $points['modern']);
        self::assertSame(6.0, $points['pioneer']);
        self::assertSame(7.0, $points['standard']);
    }

    public function testRawPointsExposeValuesWithoutPrecedence(): void
    {
        $card = new DummyMTGCard()
            ->setPointsBaseQuadruples(null)
            ->setPointsBaseSingleton(9.0)
            ->setPointsCommander(null)
            ->setPointsCommanderSpecial(10.0)
            ->setPointsDuelCommander(11.0)
            ->setPointsDuelCommanderSpecial(null)
            ->setPointsHighlander(12.0)
            ->setPointsModern(null)
            ->setPointsPioneer(13.0)
            ->setPointsStandard(null)
            ->setPoints2HG(14.0)
            ->setPoints2HGSpecial(null);

        $raw = $card->getRawPointsAsArray();

        self::assertNull($raw['basequadruples']);
        self::assertSame(9.0, $raw['basesingletonrules']);
        self::assertNull($raw['commander']);
        self::assertSame(10.0, $raw['commanderspecial']);
        self::assertSame(11.0, $raw['duelcommander']);
        self::assertNull($raw['duelcommanderspecial']);
        self::assertSame(12.0, $raw['highlander']);
        self::assertNull($raw['modern']);
        self::assertSame(13.0, $raw['pioneer']);
        self::assertNull($raw['standard']);
        self::assertSame(14.0, $raw['2hg']);
        self::assertNull($raw['2hgspecial']);
    }

    public function testSingletonPrecedenceAndSpecialFallbacks(): void
    {
        $card = new DummyMTGCard()
            ->setPointsBaseSingleton(3.0)      // basesingletonrules
            ->setPointsHighlander(null)        // falls back to basesingletonrules
            ->setPointsDuelCommander(null)     // falls back to basesingletonrules
            ->setPointsDuelCommanderSpecial(null) // falls back to duelcommander
            ->setPointsCommander(4.0)          // overrides basesingletonrules
            ->setPointsCommanderSpecial(null); // falls back to commander

        $points = $card->getCalculatorPointsAsArray();

        self::assertSame(3.0, $points['basesingletonrules']);
        self::assertSame(3.0, $points['highlander']);
        self::assertSame(3.0, $points['duelcommander']);
        self::assertSame(3.0, $points['duelcommanderspecial']); // fallback
        self::assertSame(4.0, $points['commander']);
        self::assertSame(4.0, $points['commanderspecial']); // fallback
    }

    public function testSingletonSpecialOverridesFallback(): void
    {
        $card = new DummyMTGCard()
            ->setPointsBaseSingleton(2.0)
            ->setPointsDuelCommander(5.0)
            ->setPointsDuelCommanderSpecial(6.0)
            ->setPointsCommander(7.0)
            ->setPointsCommanderSpecial(8.0);

        $points = $card->getCalculatorPointsAsArray();

        self::assertSame(5.0, $points['duelcommander']);
        self::assertSame(6.0, $points['duelcommanderspecial']); // explicit special
        self::assertSame(7.0, $points['commander']);
        self::assertSame(8.0, $points['commanderspecial']); // explicit special
    }
}
