<?php

declare(strict_types = 1);

namespace App\Tests\Entity\MTG;

use App\Entity\MTG\MTGPointsListCard;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @small
 */
final class MTGPointsListCardTest extends TestCase
{
    public function testCardName(): void
    {
        $card = new MTGPointsListCard();
        $name = 'Black Lotus';

        $card->setNameEN($name);

        self::assertSame($name, $card->getNameEN());
    }

    public function testEntityCreation(): void
    {
        $card = new MTGPointsListCard();

        self::assertInstanceOf(MTGPointsListCard::class, $card);
        self::assertNull($card->id);
    }
}
