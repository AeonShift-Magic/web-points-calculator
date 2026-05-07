<?php

declare(strict_types = 1);

namespace App\Tests\Model\MTG\Source\Download\Scryfall\V1;

use App\Model\MTG\Source\Download\Scryfall\V1\MTGScryfallDefaultCardsSourceDownloadModelV1;
use App\Model\MTG\Source\Factory\SourceActivityHistoryFactory;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Symfony\Component\Lock\LockFactory;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

/**
 * @internal
 *
 * @small
 */
final class MTGScryfallDefaultCardsSourceDownloadModelV1Test extends TestCase
{
    private string $bulkDataType = 'default_cards';

    private string $cardsSourceDir = 'var/sources/mtg';

    private EntityManagerInterface&Stub $entityManager;

    private HttpClientInterface&Stub $httpClient;

    private LockFactory&Stub $lockFactory;

    private string $projectDir = '/tmp/project';

    private string $scryfallBulkApiUrl = 'https://api.scryfall.com/bulk-data';

    private SourceActivityHistoryFactory&Stub $sourceActivityHistoryFactory;

    public function testDownloadLatestDefaultCardsThrowsWhenEntryNotFound(): void
    {
        $model = $this->createModel();

        // Bulk data without a matching entry for $this->bulkDataType
        $bulkData = [
            'data' => [
                ['type' => 'other_type', 'name' => 'Other'],
            ],
        ];

        $response = self::createStub(ResponseInterface::class);
        $response->method('toArray')->willReturn($bulkData);

        $this->httpClient
            ->method('request')
            ->willReturn($response);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(sprintf('Could not find "%s" entry in bulk data response', $this->bulkDataType));

        // Should throw before attempting any filesystem/lock operations
        $model->downloadLatestDefaultCards();
    }

    public function testFindDefaultCardsEntryReturnsMatchingEntry(): void
    {
        $model = $this->createModel();

        $bulkData = [
            'data' => [
                ['type' => 'other_type', 'name' => 'Other'],
                ['type' => 'default_cards', 'name' => 'Default Cards', 'download_uri' => 'https://example.com/file.json'],
            ],
        ];

        $entry = $model->findDefaultCardsEntry($bulkData);

        self::assertIsArray($entry);
        self::assertSame('Default Cards', $entry['name'] ?? null);
        self::assertSame('https://example.com/file.json', $entry['download_uri'] ?? null);
    }

    public function testFindDefaultCardsEntryReturnsNullWhenDataMissing(): void
    {
        $model = $this->createModel();

        $bulkData = []; // No 'data' key

        $entry = $model->findDefaultCardsEntry($bulkData);

        self::assertNull($entry);
    }

    public function testFindDefaultCardsEntryReturnsNullWhenNoMatch(): void
    {
        $model = $this->createModel();

        $bulkData = [
            'data' => [
                ['type' => 'other_type', 'name' => 'Other'],
                ['type' => 'still_not_default', 'name' => 'Not Default'],
            ],
        ];

        $entry = $model->findDefaultCardsEntry($bulkData);

        self::assertNull($entry);
    }

    public function testGetBulkDataInfoReturnsArrayFromHttpClient(): void
    {
        $model = $this->createModel();

        $response = self::createStub(ResponseInterface::class);
        $expected = ['hello' => 'world'];
        $response->method('toArray')->willReturn($expected);

        $this->httpClient
            ->method('request')
            ->willReturn($response);

        $result = $model->getBulkDataInfo();

        self::assertSame($expected, $result);
    }

    protected function setUp(): void
    {
        $this->httpClient = self::createStub(HttpClientInterface::class);
        $this->entityManager = self::createStub(EntityManagerInterface::class);
        $this->sourceActivityHistoryFactory = self::createStub(SourceActivityHistoryFactory::class);
        $this->lockFactory = self::createStub(LockFactory::class);

        // The factory will not be used by the methods under test; return a dummy interface stub to satisfy constructor.
        $dummyHistory = self::createStub(\App\Entity\SourceActivityHistoryInterface::class);
        $this->sourceActivityHistoryFactory
            ->method('create')
            ->willReturn($dummyHistory);
    }

    private function createModel(): MTGScryfallDefaultCardsSourceDownloadModelV1
    {
        return new MTGScryfallDefaultCardsSourceDownloadModelV1(
            $this->httpClient,
            $this->entityManager,
            $this->projectDir,
            $this->scryfallBulkApiUrl,
            $this->cardsSourceDir,
            $this->bulkDataType,
            $this->sourceActivityHistoryFactory,
            $this->lockFactory
        );
    }
}
