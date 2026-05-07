<?php

declare(strict_types = 1);

namespace App\Tests\Model\AeonShift\PointsList\MTG\V1;

use App\Entity\MTG\MTGPointsList;
use App\Entity\MTG\MTGPointsListCard;
use App\Entity\MTG\MTGPointsListMValue;
use App\Model\AeonShift\PointsList\MTG\V1\MTGPointsListModelV1;
use App\Repository\MValueItemsRepositoryInterface;
use App\Repository\SourceItemsRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\MockObject\Stub;
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
    private EntityManagerInterface&Stub $entityManager;

    private MValueItemsRepositoryInterface&Stub $mValueItemsRepository;

    private MTGPointsListModelV1 $model;

    private SourceItemsRepositoryInterface&Stub $sourceCardRepository;

    private TranslatorInterface&Stub $translator;

    public function testConstants(): void
    {
        // Assert
        self::assertSame('MTGPointsListModelV1.js.twig', MTGPointsListModelV1::CALCULATOR_JS_FILE);
        self::assertSame('MTGPointsListModelV1', MTGPointsListModelV1::CALCULATOR_JS_FUNCTION_PREFIX);
        self::assertSame('Initial Points List Model', MTGPointsListModelV1::LABEL);
        self::assertSame('2026-01-26', MTGPointsListModelV1::RELEASE_DATE);
        self::assertSame('((unranked))', MTGPointsListModelV1::UNRANKED_CARD_NAME);
        self::assertSame(1, MTGPointsListModelV1::VERSION);
    }

    public function testGenerateCSVResponseForListReturnsStreamedResponse(): void
    {
        // Arrange
        $pointsList = self::createStub(MTGPointsList::class);
        $pointsList->id = 1;
        $pointsList->method('getTitle')->willReturn('Test List');

        $queryBuilder = self::createStub(QueryBuilder::class);
        $query = self::createStub(Query::class);

        $this->entityManager->method('createQueryBuilder')->willReturn($queryBuilder);
        $queryBuilder->method('select')->willReturnSelf();
        $queryBuilder->method('from')->willReturnSelf();
        $queryBuilder->method('where')->willReturnSelf();
        $queryBuilder->method('orderBy')->willReturnSelf();
        $queryBuilder->method('setParameter')->willReturnSelf();
        $queryBuilder->method('getQuery')->willReturn($query);
        $query->method('getResult')->willReturn([]);

        $this->translator->method('trans')->willReturn('Translated Text');

        // Act
        $response = $this->model->generateCSVResponseForList($pointsList);

        // Assert
        self::assertSame('text/csv', $response->headers->get('Content-Type'));
        self::assertStringContainsString('attachment', $response->headers->get('Content-Disposition'));
        self::assertStringContainsString('.csv', $response->headers->get('Content-Disposition'));
    }

    public function testGenerateCSVResultsFileNameForPointsList(): void
    {
        // Arrange
        $pointsList = new MTGPointsList();
        $pointsList->id = 42;
        $pointsList->setTitle('My Test Points List!');

        // Act
        $filename = $this->model->generateCSVResultsFileNameForPointsList($pointsList);

        // Assert
        self::assertStringStartsWith('AeonShift_MTG_List_42_', $filename);
        self::assertStringEndsWith('_Points.csv', $filename);
        self::assertStringContainsString('My_Test_Points_List_', $filename);
    }

    public function testGenerateCSVResultsFileNameForPointsListWithSpecialCharacters(): void
    {
        // Arrange
        $pointsList = new MTGPointsList();
        $pointsList->id = 123;
        $pointsList->setTitle('Test @#$% List & More!');

        // Act
        $filename = $this->model->generateCSVResultsFileNameForPointsList($pointsList);

        // Assert
        self::assertStringStartsWith('AeonShift_MTG_List_123_', $filename);
        self::assertStringEndsWith('_Points.csv', $filename);

        // Special characters should be replaced with underscores
        self::assertMatchesRegularExpression(
            '/^AeonShift_MTG_List_123_[A-Za-z0-9_]+_Points\.csv$/',
            $filename
        );
    }

    public function testGenerateCSVResultsFileNameTruncatesLongTitles(): void
    {
        // Arrange
        $pointsList = new MTGPointsList();
        $pointsList->id = 1;

        $longTitle = str_repeat('A', 100);
        $pointsList->setTitle($longTitle);

        // Act
        $filename = $this->model->generateCSVResultsFileNameForPointsList($pointsList);

        // Assert - Title should be truncated to 50 characters
        self::assertLessThanOrEqual(100, mb_strlen($filename));
        self::assertStringStartsWith('AeonShift_MTG_List_1_', $filename);
    }

    public function testMergeMTGSourceAddsMValuePoints(): void
    {
        // Arrange
        $mValue = self::createStub(MTGPointsListMValue::class);
        $mValue->method('getNameEN')->willReturn('Black Lotus');
        $mValue->method('getValuePoints')->willReturn(100.0);

        $pointsList = self::createStub(MTGPointsList::class);
        $pointsList->method('getItems')->willReturn([]);

        $sourceCards = [
            [
                'name_en'          => 'Black Lotus',
                'types'            => 'Artifact',
                'color_identity'   => '[]',
                'mana_value'       => 0.0,
                'first_printed_at' => '1993-08-05',
            ],
        ];

        $this->sourceCardRepository->method('getAllItemsAsArray')->willReturn($sourceCards);
        $this->mValueItemsRepository->method('getAllItemsAsArray')->willReturn([$mValue]);

        // Act
        $result = $this->model->mergeMTGSourceAndPointsListAsArray($this->sourceCardRepository, $pointsList);

        // Assert
        self::assertArrayHasKey('Black Lotus', $result['cards']);
        self::assertSame(100.0, $result['cards']['Black Lotus']['mvaluepoints']);
    }

    public function testMergeMTGSourceAddsPointsListValues(): void
    {
        // Arrange
        $pointsListCard = self::createStub(MTGPointsListCard::class);
        $pointsListCard->method('getNameEN')->willReturn('Lightning Bolt');
        $pointsListCard->method('getPointsBaseSingleton')->willReturn(5.0);
        $pointsListCard->method('getPointsBaseQuadruples')->willReturn(3.0);
        $pointsListCard->method('getPointsDuelCommander')->willReturn(4.0);

        $pointsList = self::createStub(MTGPointsList::class);
        $pointsList->method('getItems')->willReturn([$pointsListCard]);

        $sourceCards = [
            [
                'name_en'          => 'Lightning Bolt',
                'types'            => 'Instant',
                'color_identity'   => '["R"]',
                'mana_value'       => 1.0,
                'first_printed_at' => '1993-08-05',
            ],
        ];

        $this->sourceCardRepository->method('getAllItemsAsArray')->willReturn($sourceCards);
        $this->mValueItemsRepository->method('getAllItemsAsArray')->willReturn([]);

        // Act
        $result = $this->model->mergeMTGSourceAndPointsListAsArray($this->sourceCardRepository, $pointsList);

        // Assert
        self::assertArrayHasKey('Lightning Bolt', $result['cards']);
        self::assertSame(5.0, $result['cards']['Lightning Bolt']['pointsBaseSingleton']);
        self::assertSame(3.0, $result['cards']['Lightning Bolt']['pointsBaseQuadruples']);
        self::assertSame(4.0, $result['cards']['Lightning Bolt']['pointsDuelCommander']);
        self::assertArrayHasKey('Lightning Bolt', $result['ranked']);
    }

    public function testMergeMTGSourceAndPointsListAsArrayReturnsCorrectStructure(): void
    {
        // Arrange
        $pointsList = self::createStub(MTGPointsList::class);
        $pointsList->method('getItems')->willReturn([]);
        $pointsList->method('getPValueBaseSingletonStandardPlay')->willReturn(100.0);
        $pointsList->method('getPValueBaseQuadruplesStandardPlay')->willReturn(200.0);

        $sourceCards = [
            [
                'name_en'                           => 'Lightning Bolt',
                'flavor_of_name_en'                 => null,
                'alternate_name_en'                 => null,
                'image_url'                         => 'https://example.com/image.jpg',
                'types'                             => 'Instant',
                'mana_value'                        => 1.0,
                'multi_cz_type'                     => '',
                'color_identity'                    => '["R"]',
                'maximum_timeline_legality'         => 'eternal',
                'mvalue_trend'                      => '5.50',
                'mtgoprice'                         => '2.00',
                'first_printed_year'                => 1993,
                'first_printed_at'                  => '1993-08-05',
                'is_legal_2hg'                      => true,
                'is_legal_2hg_special'              => false,
                'is_legal_duel_commander'           => true,
                'is_legal_duel_commander_special'   => false,
                'is_legal_commander'                => true,
                'is_legal_commander_special'        => false,
                'is_command_zone_eligible'          => false,
                'is_multiple_command_zone_eligible' => false,
                'max_copies'                        => 4,
                'is_black'                          => false,
                'is_blue'                           => false,
                'is_red'                            => true,
                'is_green'                          => false,
                'is_white'                          => false,
                'is_colorless'                      => false,
                'duel_rank'                         => null,
                'ffarank'                           => null,
                'cedhrank'                          => null,
                'oracle_text'                       => 'Lightning Bolt deals 3 damage to any target.',
            ],
        ];

        $this->sourceCardRepository->method('getAllItemsAsArray')->willReturn($sourceCards);
        $this->mValueItemsRepository->method('getAllItemsAsArray')->willReturn([]);

        // Act
        $result = $this->model->mergeMTGSourceAndPointsListAsArray($this->sourceCardRepository, $pointsList);

        // Assert
        self::assertIsArray($result);
        self::assertArrayHasKey('cards', $result);
        self::assertArrayHasKey('unranked', $result);
        self::assertArrayHasKey('ranked', $result);
        self::assertArrayHasKey('pvalues', $result);
        self::assertArrayHasKey('timelineprecedences', $result);
        self::assertArrayHasKey('calculatorJsFunctionPrefix', $result);

        self::assertArrayHasKey('Lightning Bolt', $result['cards']);
        self::assertSame('Instant', $result['cards']['Lightning Bolt']['types']);
        self::assertSame(1.0, $result['cards']['Lightning Bolt']['mv']);
        self::assertTrue($result['cards']['Lightning Bolt']['r']);
        self::assertFalse($result['cards']['Lightning Bolt']['b']);
    }

    public function testMergeMTGSourceDefaultsMValuePointsToZero(): void
    {
        // Arrange
        $pointsList = self::createStub(MTGPointsList::class);
        $pointsList->method('getItems')->willReturn([]);

        $sourceCards = [
            [
                'name_en'          => 'Island',
                'types'            => 'Basic Land',
                'color_identity'   => '[]',
                'mana_value'       => 0.0,
                'first_printed_at' => '1993-08-05',
            ],
        ];

        $this->sourceCardRepository->method('getAllItemsAsArray')->willReturn($sourceCards);
        $this->mValueItemsRepository->method('getAllItemsAsArray')->willReturn([]);

        // Act
        $result = $this->model->mergeMTGSourceAndPointsListAsArray($this->sourceCardRepository, $pointsList);

        // Assert
        self::assertArrayHasKey('Island', $result['cards']);
        self::assertSame(0.0, $result['cards']['Island']['mvaluepoints']);
    }

    public function testMergeMTGSourceHandlesBackgroundCards(): void
    {
        // Arrange
        $pointsList = self::createStub(MTGPointsList::class);
        $pointsList->method('getItems')->willReturn([]);

        $sourceCards = [
            [
                'name_en'          => 'Agent of the Iron Throne',
                'types'            => 'Legendary Enchantment — Background',
                'color_identity'   => '["B"]',
                'oracle_text'      => 'Commander creatures you own have "Whenever this creature attacks..."',
                'mana_value'       => 2.0,
                'first_printed_at' => '2022-06-10',
            ],
        ];

        $this->sourceCardRepository->method('getAllItemsAsArray')->willReturn($sourceCards);
        $this->mValueItemsRepository->method('getAllItemsAsArray')->willReturn([]);

        // Act
        $result = $this->model->mergeMTGSourceAndPointsListAsArray($this->sourceCardRepository, $pointsList);

        // Assert
        self::assertArrayHasKey('Agent of the Iron Throne', $result['cards']);
        self::assertTrue($result['cards']['Agent of the Iron Throne']['isbg'] ?? false);
    }

    public function testMergeMTGSourceHandlesChooseABackgroundCards(): void
    {
        // Arrange
        $pointsList = self::createStub(MTGPointsList::class);
        $pointsList->method('getItems')->willReturn([]);

        $sourceCards = [
            [
                'name_en'          => 'Test Commander',
                'types'            => 'Legendary Creature — Human',
                'color_identity'   => '["W"]',
                'oracle_text'      => 'Choose a Background',
                'mana_value'       => 3.0,
                'first_printed_at' => '2022-06-10',
            ],
        ];

        $this->sourceCardRepository->method('getAllItemsAsArray')->willReturn($sourceCards);
        $this->mValueItemsRepository->method('getAllItemsAsArray')->willReturn([]);

        // Act
        $result = $this->model->mergeMTGSourceAndPointsListAsArray($this->sourceCardRepository, $pointsList);

        // Assert
        self::assertArrayHasKey('Test Commander', $result['cards']);
        self::assertTrue($result['cards']['Test Commander']['hasbg'] ?? false);
    }

    public function testMergeMTGSourceHandlesUnrankedCard(): void
    {
        // Arrange
        $unrankedCard = self::createStub(MTGPointsListCard::class);
        $unrankedCard->method('getNameEN')->willReturn('((unranked))');
        $unrankedCard->method('getPointsBaseSingleton')->willReturn(1.0);
        $unrankedCard->method('getPointsBaseQuadruples')->willReturn(1.0);

        $pointsList = self::createStub(MTGPointsList::class);
        $pointsList->method('getItems')->willReturn([$unrankedCard]);

        $this->sourceCardRepository->method('getAllItemsAsArray')->willReturn([]);
        $this->mValueItemsRepository->method('getAllItemsAsArray')->willReturn([]);

        // Act
        $result = $this->model->mergeMTGSourceAndPointsListAsArray($this->sourceCardRepository, $pointsList);

        // Assert
        self::assertArrayHasKey('unranked', $result);
        self::assertSame(1.0, $result['unranked']['pointsBaseSingleton']);
        self::assertSame(1.0, $result['unranked']['pointsBaseQuadruples']);
        self::assertTrue($result['unranked']['czeligible']);
    }

    public function testProcessCSVStringReturnsErrorForEmptyContent(): void
    {
        // Arrange
        $pointsList = $this->createStub(MTGPointsList::class);
        $this->translator->method('trans')->willReturn('Error message');

        // Act
        $result = $this->model->processCSVString('', $pointsList);

        // Assert
        self::assertSame('error', $result['status']);
        self::assertArrayHasKey('message', $result);
    }

    public function testProcessCSVStringValidatesUnrankedCardOnFirstLine(): void
    {
        // Arrange
        $csvContent = <<<'CSV'
            Header Line 1
            Header Line 2
            Header Line 3
            Header Line 4
            Header Line 5
            Standard Play,100,100,100,,100,,100,100,100,100
            Lite Play,80,80,80,,80,,80,80,80,80
            Power Play,120,120,120,,120,,120,120,120,120
            Invalid Card Name,5,5,5,5,5,5,5,5,5,5
            CSV;

        $pointsList = self::createStub(MTGPointsList::class);
        $this->translator->method('trans')->willReturn('Error: First card must be unranked');

        // Act
        $result = $this->model->processCSVString($csvContent, $pointsList);

        // Assert
        self::assertSame('error', $result['status']);
    }

    public function testTimelinePrecedencesConstant(): void
    {
        // Assert
        self::assertSame(-1, MTGPointsListModelV1::TIMELINE_PRECEDENCES['unranked']);
        self::assertSame(10, MTGPointsListModelV1::TIMELINE_PRECEDENCES['printed']);
        self::assertSame(20, MTGPointsListModelV1::TIMELINE_PRECEDENCES['funny']);
        self::assertSame(30, MTGPointsListModelV1::TIMELINE_PRECEDENCES['eternal']);
        self::assertSame(40, MTGPointsListModelV1::TIMELINE_PRECEDENCES['modern']);
        self::assertSame(50, MTGPointsListModelV1::TIMELINE_PRECEDENCES['pioneer']);
        self::assertSame(60, MTGPointsListModelV1::TIMELINE_PRECEDENCES['standard']);
    }

    protected function setUp(): void
    {
        $this->entityManager = self::createStub(EntityManagerInterface::class);
        $this->translator = self::createStub(TranslatorInterface::class);
        $this->sourceCardRepository = self::createStub(SourceItemsRepositoryInterface::class);
        $this->mValueItemsRepository = self::createStub(MValueItemsRepositoryInterface::class);
        $security = self::createStub(Security::class);

        $this->model = new MTGPointsListModelV1(
            $this->entityManager,
            $this->translator,
            $this->sourceCardRepository,
            $this->mValueItemsRepository,
            $security
        );
    }
}
