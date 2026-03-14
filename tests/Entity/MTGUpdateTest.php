<?php

declare(strict_types = 1);

namespace App\Tests\Entity;

use App\Entity\MTG\MTGUpdate;
use DateTime;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @small
 */
final class MTGUpdateTest extends TestCase
{
    public function testCreatedAt(): void
    {
        $update = new MTGUpdate();
        $date = new DateTime();

        $update->createdAt = $date;

        self::assertSame($date, $update->createdAt);
    }

    public function testEntityCreation(): void
    {
        $update = new MTGUpdate();

        self::assertInstanceOf(MTGUpdate::class, $update);
        self::assertNull($update->id);
    }

    public function testGetTitleForForms(): void
    {
        $update = new MTGUpdate();
        $update->setTitleEN('Test Title');
        $date = new DateTime('2024-01-01');
        $update->setStartingAt($date);

        $expected = 'Test Title (2024-01-01 00:00 > 2026-04-27 19:59)';

        self::assertSame($expected, $update->getTitleForForms());
    }

    public function testIsPublic(): void
    {
        $update = new MTGUpdate();

        $update->setIsPublic(true);
        self::assertTrue($update->isPublic());

        $update->setIsPublic(false);
        self::assertFalse($update->isPublic());
    }

    public function testStartingAt(): void
    {
        $update = new MTGUpdate();
        $date = new DateTime('2024-01-01 10:00:00');

        $update->setStartingAt($date);

        self::assertSame($date, $update->getStartingAt());
    }

    public function testTitle(): void
    {
        $update = new MTGUpdate();
        $title = 'Test Title';

        $update->setTitleEN($title);

        self::assertSame($title, $update->getTitleEN());
    }

    public function testTitleWithEmptyString(): void
    {
        $update = new MTGUpdate();
        $update->setTitleEN('');

        self::assertSame('', $update->getTitleEN());
    }

    public function testUpdatedAt(): void
    {
        $update = new MTGUpdate();
        $date = new DateTime();

        $update->updatedAt = $date;

        self::assertSame($date, $update->updatedAt);
    }
}
