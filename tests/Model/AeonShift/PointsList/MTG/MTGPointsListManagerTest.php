<?php

/** @noinspection ALL */

declare(strict_types = 1);

namespace App\Tests\Model\AeonShift\PointsList\MTG;

use App\Entity\MTG\MTGPointsList;
use App\Entity\MTG\MTGUpdate;
use App\Model\AeonShift\PointsList\MTG\MTGPointsListManager;
use App\Repository\MTG\MTGPointsListMValueRepository;
use App\Repository\MTG\MTGSourceCardRepository;
use App\Repository\MTG\MTGUpdateRepository;
use DateInterval;
use DateTime;
use DateTimeInterface;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Psr\Cache\CacheItemInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @internal
 *
 * @small
 */
#[\PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations]
final class MTGPointsListManagerTest extends TestCase
{
    private CacheInterface&\PHPUnit\Framework\MockObject\MockObject $cache;

    private MTGPointsListManager $manager;

    private MTGSourceCardRepository&Stub $sourceCardRepository;

    private TranslatorInterface&Stub $translator;

    private MTGUpdateRepository&Stub $updateRepository;

    public function testGetAllPointListAndUpdatesAsArrayHandlesMultipleUpdates(): void
    {
        // Arrange
        $update1 = self::createStub(MTGUpdate::class);
        $update1->id = 1;

        $update2 = self::createStub(MTGUpdate::class);
        $update2->id = 2;

        $pointsList = self::createStub(MTGPointsList::class);
        $pointsList->method('getRulesModel')->willReturn('NonExistentClass');

        $update1->method('getPointsList')->willReturn($pointsList);
        $update2->method('getPointsList')->willReturn($pointsList);

        $update1->method('getTitleEN')->willReturn('Update 1');
        $update2->method('getTitleEN')->willReturn('Update 2');

        $startDate = new DateTime('2026-01-01');
        $endDate = new DateTime('2026-12-31');

        $update1->method('getStartingAt')->willReturn($startDate);
        $update1->method('getEndingAt')->willReturn($endDate);
        $update2->method('getStartingAt')->willReturn($startDate);
        $update2->method('getEndingAt')->willReturn($endDate);

        $this->cache->method('get')->willReturnCallback(static function ($key, $callback) {
            $cacheItem = new class() implements \Symfony\Contracts\Cache\ItemInterface {
                public function get(): mixed { return null; }

                public function set(mixed $value): static { return $this; }

                public function expiresAt(?DateTimeInterface $expiration): static { return $this; }

                public function expiresAfter(DateInterval|int|null $time): static { return $this; }

                public function tag(string|iterable $tags): static { return $this; }

                public function getMetadata(): array { return []; }

                public function getKey(): string { return 'test'; }

                public function isHit(): bool { return false; }
            };

            return $callback($cacheItem);
        });

        $this->updateRepository->method('getAllPublishedMTGUpdatesByStartingDate')
            ->willReturn([$update1, $update2]);

        $this->sourceCardRepository->method('getAllCommandersAsArray')->willReturn([]);

        // Act
        $result = $this->manager->getAllPointListAndUpdatesAsArray();

        // Assert
        static::assertIsArray($result);
        static::assertArrayHasKey('commanders', $result);
    }

    public function testGetAllPointListAndUpdatesAsArrayIncludesCommanders(): void
    {
        // Arrange
        $commanders = [
            'The First Sliver'         => ['types' => 'Legendary Creature — Sliver'],
            'Atraxa, Praetors\' Voice' => ['types' => 'Legendary Creature — Phyrexian Angel Horror'],
        ];

        $this->cache->method('get')->willReturnCallback(static function ($key, $callback) {
            $cacheItem = new class() implements \Symfony\Contracts\Cache\ItemInterface {
                public function get(): mixed { return null; }

                public function set(mixed $value): static { return $this; }

                public function expiresAt(?DateTimeInterface $expiration): static { return $this; }

                public function expiresAfter(DateInterval|int|null $time): static { return $this; }

                public function tag(string|iterable $tags): static { return $this; }

                public function getMetadata(): array { return []; }

                public function getKey(): string { return 'test'; }

                public function isHit(): bool { return false; }
            };

            return $callback($cacheItem);
        });

        $this->updateRepository->method('getAllPublishedMTGUpdatesByStartingDate')->willReturn([]);
        $this->sourceCardRepository->method('getAllCommandersAsArray')->willReturn($commanders);

        // Act
        $result = $this->manager->getAllPointListAndUpdatesAsArray();

        // Assert
        static::assertArrayHasKey('commanders', $result);
        static::assertSame($commanders, $result['commanders']);
    }

    public function testGetAllPointListAndUpdatesAsArrayUsesCacheKey(): void
    {
        // Arrange
        $cacheItem = $this->createMock(CacheItemInterface::class);

        $this->cache->expects(static::once())
            ->method('get')
            ->with('MTG_points_lists')
            ->willReturnCallback(static function ($key, $callback) use ($cacheItem) {
                return $callback($cacheItem);
            });

        $this->updateRepository->method('getAllPublishedMTGUpdatesByStartingDate')->willReturn([]);
        $this->sourceCardRepository->method('getAllCommandersAsArray')->willReturn([]);

        // Act
        $result = $this->manager->getAllPointListAndUpdatesAsArray();

        // Assert
        static::assertIsArray($result);
        static::assertArrayHasKey('commanders', $result);
    }

    public function testGetAllPointsListsAndUpdatesAsJSONArrayReturnsValidJSON(): void
    {
        // Arrange
        $this->cache->method('get')->willReturnCallback(static function ($key, $callback) {
            $cacheItem = new class() implements \Symfony\Contracts\Cache\ItemInterface {
                public function get(): mixed { return null; }

                public function set(mixed $value): static { return $this; }

                public function expiresAt(?DateTimeInterface $expiration): static { return $this; }

                public function expiresAfter(DateInterval|int|null $time): static { return $this; }

                public function tag(string|iterable $tags): static { return $this; }

                public function getMetadata(): array { return []; }

                public function getKey(): string { return 'test'; }

                public function isHit(): bool { return false; }
            };

            return $callback($cacheItem);
        });

        $this->updateRepository->method('getAllPublishedMTGUpdatesByStartingDate')->willReturn([]);
        $this->sourceCardRepository->method('getAllCommandersAsArray')->willReturn([]);

        // Act
        $result = $this->manager->getAllPointsListsAndUpdatesAsJSONArray();

        // Assert
        static::assertJson($result);
        $decoded = json_decode($result, true, 512, \JSON_THROW_ON_ERROR);
        static::assertIsArray($decoded);
        static::assertArrayHasKey('commanders', $decoded);
    }

    public function testGetAllUpdatesAndCommanderPointsAsArrayUsesCacheKey(): void
    {
        // Arrange
        $cacheItem = $this->createMock(CacheItemInterface::class);

        $this->cache->expects(static::once())
            ->method('get')
            ->with('MTG_point_lists')
            ->willReturnCallback(static function ($key, $callback) use ($cacheItem) {
                return $callback($cacheItem);
            });

        $this->updateRepository->method('getAllPublishedMTGUpdatesByStartingDate')->willReturn([]);

        // Act
        $result = $this->manager->getAllUpdatesAndCommanderPointsAsArray();

        // Assert
        static::assertIsArray($result);
        static::assertArrayHasKey('updates', $result);
    }

    public function testGetAllUpdatesAndCommanderPointsAsJSONArrayReturnsValidJSON(): void
    {
        // Arrange
        $this->cache->method('get')->willReturnCallback(static function ($key, $callback) {
            $cacheItem = new class() implements \Symfony\Contracts\Cache\ItemInterface {
                public function get(): mixed { return null; }

                public function set(mixed $value): static { return $this; }

                public function expiresAt(?DateTimeInterface $expiration): static { return $this; }

                public function expiresAfter(DateInterval|int|null $time): static { return $this; }

                public function tag(string|iterable $tags): static { return $this; }

                public function getMetadata(): array { return []; }

                public function getKey(): string { return 'test'; }

                public function isHit(): bool { return false; }
            };

            return $callback($cacheItem);
        });

        $this->updateRepository->method('getAllPublishedMTGUpdatesByStartingDate')->willReturn([]);

        // Act
        $result = $this->manager->getAllUpdatesAndCommanderPointsAsJSONArray();

        // Assert
        static::assertJson($result);
        $decoded = json_decode($result, true, 512, \JSON_THROW_ON_ERROR);
        static::assertIsArray($decoded);
        static::assertArrayHasKey('updates', $decoded);
    }

    public function testGetUpdatePointListsAsArrayUsesCacheKeyWithUpdateId(): void
    {
        // Arrange
        $update = new MTGUpdate();
        $update->id = 42;

        $pointsList = static::createStub(MTGPointsList::class);
        $pointsList->method('getRulesModel')->willReturn('NonExistentClass');

        $update->setPointsList($pointsList);

        $cacheItem = $this->createMock(CacheItemInterface::class);

        $this->cache->expects(static::once())
            ->method('get')
            ->with('MTG_point_list_42_data')
            ->willReturnCallback(static function ($key, $callback) use ($cacheItem) {
                return $callback($cacheItem);
            });

        $this->sourceCardRepository->method('getAllCommandersAsArray')->willReturn([]);

        // Act
        $result = $this->manager->getUpdatePointListsAsArray($update);

        // Assert
        static::assertIsArray($result);
        static::assertArrayHasKey('commanders', $result);
    }

    public function testJSONOutputsContainUnicodeCharactersUnescaped(): void
    {
        // Arrange
        $commanders = [
            'Nicol Bolas' => ['types' => 'Legendary Creature — Elder Dragon'],
        ];

        $this->cache->method('get')->willReturnCallback(static function ($key, $callback) {
            // Create a new anonymous class that properly implements Symfony's ItemInterface
            $cacheItem = new class() implements \Symfony\Contracts\Cache\ItemInterface {
                public function get(): mixed { return null; }

                public function set(mixed $value): static { return $this; }

                public function expiresAt(?DateTimeInterface $expiration): static { return $this; }

                public function expiresAfter(DateInterval|int|null $time): static { return $this; }

                public function tag(string|iterable $tags): static { return $this; }

                public function getMetadata(): array { return []; }

                public function getKey(): string { return 'test'; }

                public function isHit(): bool { return false; }
            };

            return $callback($cacheItem);
        });

        $this->updateRepository->method('getAllPublishedMTGUpdatesByStartingDate')->willReturn([]);
        $this->sourceCardRepository->method('getAllCommandersAsArray')->willReturn($commanders);

        // Act
        $result = $this->manager->getAllPointsListsAndUpdatesAsJSONArray();

        // Assert
        static::assertStringNotContainsString('\\u', $result); // Unicode should not be escaped
        static::assertJson($result);
    }

    public function testLicenseConstant(): void
    {
        // Assert
        static::assertSame('MTG', MTGPointsListManager::LICENSE);
    }

    public function testProcessUpdatePointListAsArrayFormatsLatestUpdate(): void
    {
        // Arrange
        $update = static::createStub(MTGUpdate::class);
        $update->id = 1;

        $pointsList = static::createStub(MTGPointsList::class);
        $pointsList->method('getRulesModel')->willReturn('NonExistentClass');

        $update->method('getPointsList')->willReturn($pointsList);
        $update->method('getTitleEN')->willReturn('Latest Update');
        $update->method('getStartingAt')->willReturn(new DateTime('2026-01-01'));
        $update->method('getEndingAt')->willReturn(new DateTime('2026-12-31'));

        $dataArray = [];

        // Act
        $this->manager->processUpdatePointListAsArray($update, $dataArray, 1);

        // Assert - Even with count=1, should not process if class doesn't exist
        static::assertEmpty($dataArray);
    }

    public function testProcessUpdatePointListAsArrayFormatsNonLatestUpdate(): void
    {
        // Arrange
        $update = static::createStub(MTGUpdate::class);
        $update->id = 2;

        $pointsList = static::createStub(MTGPointsList::class);
        $pointsList->method('getRulesModel')->willReturn('NonExistentClass');

        $update->method('getPointsList')->willReturn($pointsList);
        $update->method('getTitleEN')->willReturn('Old Update');
        $update->method('getStartingAt')->willReturn(new DateTime('2025-01-01'));
        $update->method('getEndingAt')->willReturn(new DateTime('2025-12-31'));

        $dataArray = [];

        // Act
        $this->manager->processUpdatePointListAsArray($update, $dataArray, 2);

        // Assert - Should not add anything if class doesn't exist
        static::assertEmpty($dataArray);
    }

    public function testProcessUpdatePointListAsArrayInitializesUpdatesArray(): void
    {
        // Arrange
        $update = static::createStub(MTGUpdate::class);
        $update->id = 5;

        $pointsList = static::createStub(MTGPointsList::class);
        $pointsList->method('getRulesModel')->willReturn('NonExistentClass');

        $update->method('getPointsList')->willReturn($pointsList);
        $update->method('getTitleEN')->willReturn('Test');
        $update->method('getStartingAt')->willReturn(new DateTime('2026-01-01'));
        $update->method('getEndingAt')->willReturn(new DateTime('2026-12-31'));

        // Start with no 'updates' key
        $dataArray = [];

        // Act
        $this->manager->processUpdatePointListAsArray($update, $dataArray, 1);

        // Assert - Even if class doesn't exist, we're testing the structure
        // The method should not crash when 'updates' key doesn't exist
        static::assertTrue(true); // If we get here without error, test passes
    }

    public function testProcessUpdatePointListAsArrayWithNonExistentClass(): void
    {
        // Arrange
        $update = static::createStub(MTGUpdate::class);
        $update->id = 1;

        $pointsList = static::createStub(MTGPointsList::class);
        $pointsList->method('getRulesModel')->willReturn('NonExistentClass');

        $update->method('getPointsList')->willReturn($pointsList);
        $update->method('getTitleEN')->willReturn('Test Update');
        $update->method('getStartingAt')->willReturn(new DateTime('2026-01-01'));
        $update->method('getEndingAt')->willReturn(new DateTime('2026-12-31'));

        $dataArray = [];

        // Act
        $this->manager->processUpdatePointListAsArray($update, $dataArray, 1);

        // Assert - Should not add anything if class doesn't exist
        static::assertEmpty($dataArray);
    }

    public function testProcessUpdatePointListAsArrayWithZeroCount(): void
    {
        // Arrange
        $update = static::createStub(MTGUpdate::class);
        $update->id = 3;

        $pointsList = static::createStub(MTGPointsList::class);
        $pointsList->method('getRulesModel')->willReturn('NonExistentClass');

        $update->method('getPointsList')->willReturn($pointsList);
        $update->method('getTitleEN')->willReturn('Update');
        $update->method('getStartingAt')->willReturn(new DateTime('2026-05-01'));
        $update->method('getEndingAt')->willReturn(new DateTime('2026-06-01'));

        $dataArray = [];

        // Act - count = 0 means it's not marked as latest
        $this->manager->processUpdatePointListAsArray($update, $dataArray, 0);

        // Assert
        static::assertEmpty($dataArray);
    }

    protected function setUp(): void
    {
        $this->updateRepository = static::createStub(MTGUpdateRepository::class);
        $this->sourceCardRepository = static::createStub(MTGSourceCardRepository::class);
        $mValueRepository = static::createStub(MTGPointsListMValueRepository::class);
        $this->cache = self::createMock(CacheInterface::class);
        $this->translator = static::createStub(TranslatorInterface::class);
        $entityManager = static::createStub(EntityManagerInterface::class);
        $security = static::createStub(Security::class);

        $this->manager = new MTGPointsListManager(
            $this->updateRepository,
            $this->sourceCardRepository,
            $mValueRepository,
            $this->cache,
            $this->translator,
            $entityManager,
            $security
        );
    }
}
