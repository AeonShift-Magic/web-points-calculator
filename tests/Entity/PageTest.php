<?php

declare(strict_types = 1);

namespace App\Tests\Entity;

use App\Entity\Page;
use DateTime;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @small
 */
final class PageTest extends TestCase
{
    public function testContent(): void
    {
        $page = new Page();
        $content = '<p>This is the page content</p>';

        $page->setContents($content);

        self::assertSame($content, $page->getContents());
    }

    public function testContentWithEmpty(): void
    {
        $page = new Page();

        $page->setContents('');

        self::assertSame('', $page->getContents());
    }

    public function testCreatedAt(): void
    {
        $page = new Page();
        $date = new DateTime();

        $page->createdAt = $date;

        self::assertSame($date, $page->createdAt);
    }

    public function testEntityCreation(): void
    {
        $page = new Page();

        self::assertNull($page->id);
    }

    public function testSlug(): void
    {
        $page = new Page();
        $slug = 'about-us';

        $page->setSlug($slug);

        self::assertSame($slug, $page->getSlug());
    }

    public function testTitle(): void
    {
        $page = new Page();
        $title = 'About Us';

        $page->setTitle($title);

        self::assertSame($title, $page->getTitle());
    }

    public function testUpdatedAt(): void
    {
        $page = new Page();
        $date = new DateTime();

        $page->updatedAt = $date;

        self::assertSame($date, $page->updatedAt);
    }
}
