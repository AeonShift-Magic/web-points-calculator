<?php

declare(strict_types = 1);

namespace App\Tests\Entity;

use App\Entity\CustomBlock;
use DateTime;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @small
 */
final class CustomBlockTest extends TestCase
{
    public function testContent(): void
    {
        $block = new CustomBlock();
        $content = '<div class="banner">Welcome!</div>';

        $block->setContents($content);

        self::assertSame($content, $block->getContents());
    }

    public function testCreatedAt(): void
    {
        $block = new CustomBlock();
        $date = new DateTime();

        $block->createdAt = $date;

        self::assertSame($date, $block->createdAt);
    }

    public function testCreatedBy(): void
    {
        $block = new CustomBlock();

        // Test that methods exist for relation
        self::assertTrue(method_exists($block, 'getCreatedBy'));
        self::assertTrue(method_exists($block, 'setCreatedBy'));
    }

    public function testEntityCreation(): void
    {
        $block = new CustomBlock();

        self::assertNull($block->id);
    }

    public function testUpdatedAt(): void
    {
        $block = new CustomBlock();
        $date = new DateTime();

        $block->updatedAt = $date;

        self::assertSame($date, $block->updatedAt);
    }
}
