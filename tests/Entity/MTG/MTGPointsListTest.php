<?php

declare(strict_types = 1);

namespace App\Tests\Entity\MTG;

use App\Entity\MTG\MTGPointsList;
use DateTime;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @small
 */
final class MTGPointsListTest extends TestCase
{
    public function testCreatedAt(): void
    {
        $pointList = new MTGPointsList();
        $date = new DateTime();

        $pointList->createdAt = $date;

        self::assertSame($date, $pointList->createdAt);
    }

    public function testEntityCreation(): void
    {
        $pointList = new MTGPointsList();

        // ID is null by default
        self::assertNull($pointList->id);
    }

    public function testTitle(): void
    {
        $pointList = new MTGPointsList();
        $name = 'Test Point List';

        $pointList->setTitle($name);

        self::assertSame($name, $pointList->getTitle());
    }
}
