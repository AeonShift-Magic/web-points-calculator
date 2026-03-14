<?php

declare(strict_types = 1);

namespace App\Tests\Model\MTG;

use App\Entity\MTG\MTGPointsList;
use App\Model\AeonShift\PointsList\MTG\V1\MTGPointsListModelV1;
use App\Repository\SourceItemsRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @internal
 *
 * @small
 */
final class MTGPointsListModelV1Test extends TestCase
{
    private MTGPointsListModelV1 $model;

    private TranslatorInterface $translator;

    public function testGenerateCSVResultsFileNameForListProducesSanitizedFilename(): void
    {
        $model = $this->model;

        $pointsList = new MTGPointsList();
        $pointsList->setTitle('My Points/List: Special & Chars! 2026');
        $pointsList->id = 42;

        $filename = $model->generateCSVResultsFileNameForPointsList($pointsList);

        self::assertStringStartsWith('AeonShift_MTG_List_42_', $filename);
        self::assertStringEndsWith('_Points.csv', $filename);

        $sanitized = mb_substr(preg_replace('/[^a-zA-Z0-9]/', '_', $pointsList->getTitle()), 0, 50);
        self::assertStringContainsString($sanitized, $filename);
    }

    public function testProcessCSVString(): void
    {
        $model = $this->model;

        // Configure translator stub to return a known string for assertions
        $this->translator->method('trans')->willReturn('translated_error');

        // Build a CSV with 5 header lines then an invalid P-Value line (too few columns)
        $lines = array_fill(0, 5, 'HEADER');
        $lines[] = '1,2,3'; // P-Value line with less than 11 columns to trigger error
        $csv = implode("\n", $lines);

        $pointsList = new MTGPointsList();
        $pointsList->setTitle('My Points/List: Special & Chars! 2026');
        $pointsList->id = 42;

        $result = $model->processCSVString($csv, $pointsList, 'filename');

        self::assertIsArray($result);
        self::assertArrayHasKey('status', $result);
        self::assertSame('error', $result['status']);
    }

    public function testSanitizeStringFromCSVFileCollapsesWhitespace(): void
    {
        $model = $this->model;

        $input = "  Foo\n   Bar\tBaz  ";
        $out = $model->sanitizeStringFromCSVFile($input);

        self::assertSame('Foo Bar Baz', $out);
    }

    protected function setUp(): void
    {
        $entityManager = self::createStub(EntityManagerInterface::class);
        $this->translator = self::createStub(TranslatorInterface::class);
        $repository = self::createStub(SourceItemsRepositoryInterface::class);
        $security = self::createStub(Security::class);

        $this->model = new MTGPointsListModelV1(
            $entityManager,
            $this->translator,
            $repository,
            $security
        );
    }

    // public function testGenerateCSVResponseForList(): void
    // {
    //     $this->markTestIncomplete('TODO: implement test for generateCSVResponseForList');
    // }

    // public function testGetPointsListAsJSONArray(): void
    // {
    //     $this->markTestIncomplete('TODO: implement test for getPointsListAsJSONArray');
    // }

    // public function testMergeMTGSourceAndPointsList(): void
    // {
    //     $this->markTestIncomplete('TODO: implement test for mergeMTGSourceAndPointsList');
    // }

    // public function testMergeMTGSourceAndPointsListAsArray(): void
    // {
    //     $this->markTestIncomplete('TODO: implement test for mergeMTGSourceAndPointsListAsArray');
    // }

    // public function testProcessCSVStringValidFile(): void
    // {
    //     $this->markTestIncomplete('TODO: implement success-path test for processCSVString');
    // }
}
