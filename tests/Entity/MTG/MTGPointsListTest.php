<?php

declare(strict_types = 1);

namespace App\Tests\Entity\MTG;

use App\Entity\MTG\MTGPointsList;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\TestCase;

final class MTGPointsListTest extends TestCase
{
    public function testConstructorInitializesDatesAndCollectionsAndDefaults(): void
    {
        $list = new MTGPointsList();

        // Dates initialized
        self::assertInstanceOf(DateTime::class, $list->getLastUploadedAt());
        self::assertInstanceOf(DateTime::class, $list->getValidityStartingAt());

        // Collections initialized
        self::assertCount(0, $list->getMTGPointListCards());
        self::assertCount(0, $list->getMTGUpdates());
        self::assertCount(0, $list->getMTGPointListMValues());

        // Some key defaults
        self::assertSame(1.2, $list->getMValueShippingMultiplier());
        self::assertSame(0.1, $list->getMValueShippingFloor());
        self::assertSame(50.0, $list->getMValueShippingCeiling());

        self::assertSame(100.0, $list->getPValueBaseSingletonStandardPlay());
        self::assertSame(100.0, $list->getPValueBaseQuadruplesStandardPlay());
    }

    public function testToStringContainsTitleDateAndCardCount(): void
    {
        $list = new MTGPointsList();
        $list
            ->setTitle('My List')
            ->setValidityStartingAt(new DateTime('2026-01-02 03:04'));

        // Simulate 3 cards in the list
        $cards = new ArrayCollection([new \stdClass(), new \stdClass(), new \stdClass()]);
        $list->setMTGPointListCards($cards);

        $asString = (string)$list;

        self::assertStringContainsString('My List', $asString);
        self::assertStringContainsString('[2026-01-02 03:04]', $asString);
        self::assertStringContainsString('[3 cards]', $asString);
    }

    public function testGetItemsReturnsUnderlyingCardsArray(): void
    {
        $list = new MTGPointsList();

        $cards = new ArrayCollection([new \stdClass(), new \stdClass()]);
        $list->setMTGPointListCards($cards);

        $items = $list->getItems();
        self::assertIsArray($items);
        self::assertCount(2, $items);
    }

    public function testPreventRemovalIfUpdatesExistThrows(): void
    {
        $list = new MTGPointsList();

        $updates = new ArrayCollection([new \stdClass()]);
        $list->setMTGUpdates($updates);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Cannot delete a points list that is referenced by one or more updates.');
        $list->preventRemovalIfUpdatesExist();
    }

    public function testPreventRemovalIfNoUpdatesDoesNotThrow(): void
    {
        $list = new MTGPointsList();

        $updates = new ArrayCollection([]);
        $list->setMTGUpdates($updates);

        // No exception expected
        $list->preventRemovalIfUpdatesExist();
        self::assertTrue(true);
    }

    public function testMValueSettersCastToStringAndGettersReturnFloats(): void
    {
        $list = new MTGPointsList();

        $list
            ->setMValueFactor0(1.23456789)
            ->setMValueFactor1(2.5)
            ->setMValueFactor2(-0.75)
            ->setMValueShippingMultiplier(1.75)
            ->setMValueShippingFloor(0.25)
            ->setMValueShippingCeiling(123.45);

        self::assertSame(1.23456789, $list->getMValueFactor0());
        self::assertSame(2.5, $list->getMValueFactor1());
        self::assertSame(-0.75, $list->getMValueFactor2());
        self::assertSame(1.75, $list->getMValueShippingMultiplier());
        self::assertSame(0.25, $list->getMValueShippingFloor());
        self::assertSame(123.45, $list->getMValueShippingCeiling());
    }

    public function testPValueSettersAndGetters(): void
    {
        $list = new MTGPointsList();

        $list
            ->setPValueBaseSingletonStandardPlay(10.0)
            ->setPValueBaseSingletonLitePlay(11.0)
            ->setPValueBaseSingletonPowerPlay(12.0)
            ->setPValueBaseQuadruplesStandardPlay(13.0)
            ->setPValueBaseQuadruplesLitePlay(14.0)
            ->setPValueBaseQuadruplesPowerPlay(15.0)
            ->setPValueCommanderStandardPlay(16.0)
            ->setPValueCommanderLitePlay(17.0)
            ->setPValueCommanderPowerPlay(18.0)
            ->setPValueDuelCommanderStandardPlay(19.0)
            ->setPValueDuelCommanderLitePlay(20.0)
            ->setPValueDuelCommanderPowerPlay(21.0)
            ->setPValueHighlanderStandardPlay(22.0)
            ->setPValueHighlanderLitePlay(23.0)
            ->setPValueHighlanderPowerPlay(24.0)
            ->setPValueModernStandardPlay(25.0)
            ->setPValueModernLitePlay(26.0)
            ->setPValueModernPowerPlay(27.0)
            ->setPValuePioneerStandardPlay(28.0)
            ->setPValuePioneerLitePlay(29.0)
            ->setPValuePioneerPowerPlay(30.0)
            ->setPValueStandardStandardPlay(31.0)
            ->setPValueStandardLitePlay(32.0)
            ->setPValueStandardPowerPlay(33.0)
        ;

        self::assertSame(10.0, $list->getPValueBaseSingletonStandardPlay());
        self::assertSame(11.0, $list->getPValueBaseSingletonLitePlay());
        self::assertSame(12.0, $list->getPValueBaseSingletonPowerPlay());
        self::assertSame(13.0, $list->getPValueBaseQuadruplesStandardPlay());
        self::assertSame(14.0, $list->getPValueBaseQuadruplesLitePlay());
        self::assertSame(15.0, $list->getPValueBaseQuadruplesPowerPlay());
        self::assertSame(16.0, $list->getPValueCommanderStandardPlay());
        self::assertSame(17.0, $list->getPValueCommanderLitePlay());
        self::assertSame(18.0, $list->getPValueCommanderPowerPlay());
        self::assertSame(19.0, $list->getPValueDuelCommanderStandardPlay());
        self::assertSame(20.0, $list->getPValueDuelCommanderLitePlay());
        self::assertSame(21.0, $list->getPValueDuelCommanderPowerPlay());
        self::assertSame(22.0, $list->getPValueHighlanderStandardPlay());
        self::assertSame(23.0, $list->getPValueHighlanderLitePlay());
        self::assertSame(24.0, $list->getPValueHighlanderPowerPlay());
        self::assertSame(25.0, $list->getPValueModernStandardPlay());
        self::assertSame(26.0, $list->getPValueModernLitePlay());
        self::assertSame(27.0, $list->getPValueModernPowerPlay());
        self::assertSame(28.0, $list->getPValuePioneerStandardPlay());
        self::assertSame(29.0, $list->getPValuePioneerLitePlay());
        self::assertSame(30.0, $list->getPValuePioneerPowerPlay());
        self::assertSame(31.0, $list->getPValueStandardStandardPlay());
        self::assertSame(32.0, $list->getPValueStandardLitePlay());
        self::assertSame(33.0, $list->getPValueStandardPowerPlay());
    }

    public function testGeneralSettersGettersAndFluentBehavior(): void
    {
        $list = new MTGPointsList();

        $now = new DateTime('2026-05-01 10:00:00');

        self::assertSame($list, $list->setFilename('file.csv'));
        self::assertSame($list, $list->setLastUploadedAt($now));
        self::assertSame($list, $list->setTitle('Title'));
        self::assertSame($list, $list->setRulesModel('RulesClass'));
        self::assertSame($list, $list->setRulesModelName('Rules Name'));
        self::assertSame($list, $list->setValidityStartingAt(new DateTime('2026-06-01 12:00:00')));
        self::assertSame($list, $list->setNumberOfUploadedCards(42));

        self::assertSame('file.csv', $list->getFilename());
        self::assertSame($now->getTimestamp(), $list->getLastUploadedAt()->getTimestamp());
        self::assertSame('Title', $list->getTitle());
        self::assertSame('RulesClass', $list->getRulesModel());
        self::assertSame('Rules Name', $list->getRulesModelName());
        self::assertSame(42, $list->getNumberOfUploadedCards());

        // Set collections through setters and ensure they are set
        $cards = new ArrayCollection([new \stdClass()]);
        $mvalues = new ArrayCollection([new \stdClass()]);
        $updates = new ArrayCollection([new \stdClass()]);
        $list
            ->setMTGPointListCards($cards)
            ->setMTGPointListMValues($mvalues)
            ->setMTGUpdates($updates);

        self::assertSame(1, $list->getMTGPointListCards()->count());
        self::assertSame(1, $list->getMTGPointListMValues()->count());
        self::assertSame(1, $list->getMTGUpdates()->count());
    }

    public function testSetAndGetMValuesSetAt(): void
    {
        $list = new MTGPointsList();

        self::assertNull($list->getMValuesSetAt());

        $when = new DateTime('2026-07-01 08:00:00');
        $list->setMValuesSetAt($when);

        self::assertSame($when->getTimestamp(), $list->getMValuesSetAt()?->getTimestamp());
    }
}
