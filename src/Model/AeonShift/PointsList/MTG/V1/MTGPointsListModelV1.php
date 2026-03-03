<?php

/** @noinspection UnnecessaryCastingInspection */

declare(strict_types = 1);

namespace App\Model\AeonShift\PointsList\MTG\V1;

use App\Entity\MTG\MTGPointsList;
use App\Entity\MTG\MTGPointsListCard;
use App\Entity\MTG\MTGSourceCard;
use App\Entity\PointsListInterface;
use App\Entity\User;
use App\Model\AeonShift\PointsList\AbstractPointsListModel;
use App\Model\AeonShift\PointsList\MTGPointsListModelInterface;
use App\Repository\SourceItemsRepositoryInterface;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use const JSON_THROW_ON_ERROR;
use const JSON_UNESCAPED_UNICODE;
use JsonException;
use Override;
use RuntimeException;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Contracts\Translation\TranslatorInterface;

final class MTGPointsListModelV1 extends AbstractPointsListModel implements MTGPointsListModelInterface
{
    public const string CALCULATOR_JS_FILE = 'MTGPointsListModelV1.js.twig';

    public const string CALCULATOR_JS_FUNCTION_PREFIX = 'MTGPointsListModelV1';

    public const string LABEL = 'Initial Points List Model';

    public const string RELEASE_DATE = '2026-01-26';

    /**
     * Timeline legalities precedence values.
     * Used at fast speed integer comparisons.
     *
     * @var array<string, int>
     */
    public const array TIMELINE_PRECEDENCES = [
        'unranked' => -1,
        'printed'  => 10,
        'funny'    => 20,
        'eternal'  => 30,
        'modern'   => 40,
        'pioneer'  => 50,
        'standard' => 60,
    ];

    public const string UNRANKED_CARD_NAME = '((unranked))';

    public const int VERSION = 1;

    public function __construct(
        private EntityManagerInterface $entityManager,
        private TranslatorInterface $translator,
        private SourceItemsRepositoryInterface $MTGSourceCardRepository,
        private Security $security
    )
    {
    }

    /**
     * @param MTGPointsList $pointsList
     *
     * @return StreamedResponse
     */
    #[Override]
    public function generateCSVResponseForList(PointsListInterface $pointsList): StreamedResponse
    {
        /** @var MTGPointsListCard[] $pointsListCards */
        $pointsListCards = $this->entityManager->createQueryBuilder()
            ->select('c')
            ->from(MTGPointsListCard::class, 'c')
            ->where('c.pointsList = :pointslist')
            ->orderBy('c.nameEN', 'ASC')
            ->setParameter('pointslist', $pointsList)
            ->getQuery()
            ->getResult();

        $response = new StreamedResponse(function () use ($pointsListCards, $pointsList): void {
            $handle = fopen('php://output', 'wb');

            if ($handle === false) {
                throw new RuntimeException('Failed to open output stream');
            }

            // Write headers - 5 Lines
            fputcsv(
                $handle,
                [
                    $this->translator->trans('admin.form.mtg.pointslist.import.firstline.text'),
                    '',
                    '',
                    '',
                    '',
                    '',
                    '',
                    '',
                    '',
                    '',
                    '',
                ]
            );

            // Add one helper line
            fputcsv(
                $handle,
                [
                    $this->translator->trans('admin.form.mtg.pointslistcard.create.nameen.label'),
                    $this->translator->trans('admin.form.mtg.pointslistcard.create.pointsbasesingleton.label'),
                    $this->translator->trans('admin.form.mtg.pointslistcard.create.pointsbasequadruples.label'),
                    $this->translator->trans('admin.form.mtg.pointslistcard.create.pointsduelcommander.label'),
                    $this->translator->trans('admin.form.mtg.pointslistcard.create.pointsduelcommanderspecial.label'),
                    $this->translator->trans('admin.form.mtg.pointslistcard.create.pointscommander.label'),
                    $this->translator->trans('admin.form.mtg.pointslistcard.create.pointscommanderspecial.label'),
                    $this->translator->trans('admin.form.mtg.pointslistcard.create.pointshighlander.label'),
                    $this->translator->trans('admin.form.mtg.pointslistcard.create.pointsmodern.label'),
                    $this->translator->trans('admin.form.mtg.pointslistcard.create.pointspioneer.label'),
                    $this->translator->trans('admin.form.mtg.pointslistcard.create.pointsstandard.label'),
                ]
            );

            // Add 3 empty lines
            for ($i = 1; $i <= 3; ++$i) {
                fputcsv(
                    $handle,
                    [
                        '',
                        '',
                        '',
                        '',
                        '',
                        '',
                        '',
                        '',
                        '',
                        '',
                        '',
                    ]
                );
            }

            // Add P-Values, with helper cells, in order: Standard Play, Lite Play, Power Play
            fputcsv(
                $handle,
                [
                    $this->translator->trans('admin.form.mtg.pointslist.import.pvalues.header.standardplay.text'),
                    $pointsList->getPValueBaseQuadruplesStandardPlay(),
                    $pointsList->getPValueBaseSingletonStandardPlay(),
                    $pointsList->getPValueDuelCommanderStandardPlay(),
                    '',
                    $pointsList->getPValueCommanderStandardPlay(),
                    '',
                    $pointsList->getPValueHighlanderStandardPlay(),
                    $pointsList->getPValueModernStandardPlay(),
                    $pointsList->getPValuePioneerStandardPlay(),
                    $pointsList->getPValueStandardStandardPlay(),
                ]
            );
            fputcsv(
                $handle,
                [
                    $this->translator->trans('admin.form.mtg.pointslist.import.pvalues.header.liteplay.text'),
                    $pointsList->getPValueBaseQuadruplesLitePlay(),
                    $pointsList->getPValueBaseSingletonLitePlay(),
                    $pointsList->getPValueDuelCommanderLitePlay(),
                    '',
                    $pointsList->getPValueCommanderLitePlay(),
                    '',
                    $pointsList->getPValueHighlanderLitePlay(),
                    $pointsList->getPValueModernLitePlay(),
                    $pointsList->getPValuePioneerLitePlay(),
                    $pointsList->getPValueStandardLitePlay(),
                ]
            );
            fputcsv(
                $handle,
                [
                    $this->translator->trans('admin.form.mtg.pointslist.import.pvalues.header.powerplay.text'),
                    $pointsList->getPValueBaseQuadruplesPowerPlay(),
                    $pointsList->getPValueBaseSingletonPowerPlay(),
                    $pointsList->getPValueDuelCommanderPowerPlay(),
                    '',
                    $pointsList->getPValueCommanderPowerPlay(),
                    '',
                    $pointsList->getPValueHighlanderPowerPlay(),
                    $pointsList->getPValueModernPowerPlay(),
                    $pointsList->getPValuePioneerPowerPlay(),
                    $pointsList->getPValueStandardPowerPlay(),
                ]
            );

            // Then add all points list cards
            foreach ($pointsListCards as $result) {
                fputcsv(
                    $handle,
                    [
                        $result->getNameEN(),
                        $result->getPointsBaseSingleton(),
                        $result->getPointsBaseQuadruples(),
                        $result->getPointsDuelCommander(),
                        $result->getPointsDuelCommanderSpecial(),
                        $result->getPointsCommander(),
                        $result->getPointsCommanderSpecial(),
                        $result->getPointsHighlander(),
                        $result->getPointsModern(),
                        $result->getPointsPioneer(),
                        $result->getPointsStandard(),
                    ],
                );
            }

            fclose($handle);
        });

        $response->headers->set('Content-Type', 'text/csv');
        $response->headers->set('Content-Disposition', 'attachment; filename="' . $this->generateCSVResultsFileNameForPointsList($pointsList) . '"');

        return $response;
    }

    /**
     * Simple file name generator for an exported CSV file, based on the Points List attributes.
     *
     * @param PointsListInterface $pointsList
     *
     * @return string
     */
    public function generateCSVResultsFileNameForPointsList(PointsListInterface $pointsList): string
    {
        // Generate a filename based on the Points List ID and current timestamp
        return 'AeonShift_MTG_List_'
            . (int)$pointsList->id
            . '_'
            . mb_substr(
                (string)preg_replace(
                    '/[^a-zA-Z0-9]/',
                    '_',
                    $pointsList->getTitle()
                ),
                0,
                50
            )
            . '_Points.csv';
    }

    /**
     * @param SourceItemsRepositoryInterface $entityRepository should be your Source repository
     * @param PointsListInterface $pointsList should be your Points List entity
     *
     * @throws JsonException
     *
     * @return string
     */
    public function getPointsListAsJSONArray(SourceItemsRepositoryInterface $entityRepository, PointsListInterface $pointsList): string
    {
        $mergedCards = $this->mergeMTGSourceAndPointsList($entityRepository, $pointsList);

        return json_encode($mergedCards, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    }

    /**
     * @param SourceItemsRepositoryInterface $entityRepository
     * @param PointsListInterface $pointsList
     *
     * @return array<int, MTGSourceCard>
     */
    public function mergeMTGSourceAndPointsList(SourceItemsRepositoryInterface $entityRepository, PointsListInterface $pointsList): array
    {
        /** @var array<int, MTGSourceCard> $sourceCards */
        $sourceCards = $entityRepository->getAllItemsAsArray();
        /** @var array<int, MTGPointsListCard> $pointListCards */
        $pointListCards = $pointsList->getItems();

        foreach ($pointListCards as $pointListCard) {

            if (mb_strtolower($pointListCard->getNameEN()) === self::UNRANKED_CARD_NAME) {
                $sourceCards[] = new MTGSourceCard()
                    ->setNameEN($pointListCard->getNameEN())
                    ->setFlavorOfNameEN($pointListCard->getFlavorOfNameEN())
                    ->setAlternateNameEN($pointListCard->getAlternateNameEN())
                    ->setIsLegalDuelCommander(true)
                    ->setIsLegalDuelCommanderSpecial(true)
                    ->setIsLegal2HG(true)
                    ->setIsLegal2HGSpecial(true)
                    ->setIsLegalCommander(true)
                    ->setIsLegalCommanderSpecial(true)
                    ->setFirstPrintedAt(new DateTimeImmutable('1993-08-05'))
                    ->setFirstPrintedYear(1993)
                    ->setMaximumTimelineLegality('printed')
                    ->setManaValue(0)
                    ->setIsWhite(false)
                    ->setIsBlue(false)
                    ->setIsBlack(false)
                    ->setIsGreen(false)
                    ->setIsColorless(false)
                    ->setPointsDuelCommander($pointListCard->getPointsDuelCommander())
                    ->setPointsDuelCommanderSpecial($pointListCard->getPointsDuelCommanderSpecial())
                    ->setPoints2HG($pointListCard->getPoints2HG())
                    ->setPoints2HGSpecial($pointListCard->getPoints2HGSpecial())
                    ->setPointsCommander($pointListCard->getPointsCommander())
                    ->setPointsCommanderSpecial($pointListCard->getPointsCommanderSpecial());
            }

            /** @var MTGSourceCard $sourceCard */
            foreach ($sourceCards as $sourceCard) {
                if ($sourceCard->getNameEN() === $pointListCard->getNameEN()) {
                    $sourceCard->setPointsDuelCommander($pointListCard->getPointsDuelCommander());
                    $sourceCard->setPointsDuelCommanderSpecial($pointListCard->getPointsDuelCommanderSpecial());
                    $sourceCard->setPoints2HG($pointListCard->getPoints2HG());
                    $sourceCard->setPoints2HGSpecial($pointListCard->getPoints2HGSpecial());
                    $sourceCard->setPointsCommander($pointListCard->getPointsCommander());
                    $sourceCard->setPointsCommanderSpecial($pointListCard->getPointsCommanderSpecial());

                    continue 2;
                }
            }
        }

        return $sourceCards;
    }

    /**
     * Could use DTOs, but arrays are just as fast.
     *
     * @param SourceItemsRepositoryInterface $entityRepository
     * @param MTGPointsList $pointsList
     *
     * @return array{
     *     cards: array{
     *         string, array{
     *              flavorofnameen: string|null,
     *              alternatenameen: string|null,
     *              imageurl: string|null,
     *              mv: float,
     *              multicztype: string,
     *              ci: string[],
     *              timeline: string,
     *              mvalue: float,
     *              tix: float,
     *              firstprintedyear: int,
     *              firstprintedon: int,
     *              legal2HG: bool,
     *              legal2HGSpecial: bool,
     *              legalDC: bool,
     *              legalDCSpecial: bool,
     *              legalCEDH: bool,
     *              legalCEDHSpecial: bool,
     *              czeligible: bool,
     *              multiczeligible: bool,
     *              b: bool,
     *              u: bool,
     *              r: bool,
     *              g: bool,
     *              w: bool,
     *              c: bool,
     *              pointsBaseSingleton: float|null,
     *              pointsBaseQuadruples: float|null,
     *              pointsDuelCommander: float|null,
     *              pointsDuelCommanderSpecial: float|null,
     *              pointsCommander: float|null,
     *              pointsCommanderSpecial: float|null,
     *              pointsHighlander: float|null,
     *              pointsModern: float|null,
     *              pointsPioneer: float|null,
     *              pointsStandard: float|null
     *         }
     *     },
     *     unranked: array{
     *          flavorofnameen: null,
     *          alternatenameen: string|null,
     *          imageurl: string|null,
     *          mv: float,
     *          multicztype: string,
     *          ci: string[],
     *          timeline: string,
     *          mvalue: float,
     *          tix: float,
     *          firstprintedyear: int,
     *          firstprintedon: int,
     *          legal2HG: bool,
     *          legal2HGSpecial: bool,
     *          legalDC: bool,
     *          legalDCSpecial: bool,
     *          legalCEDH: bool,
     *          legalCEDHSpecial: bool,
     *          czeligible: bool,
     *          multiczeligible: bool,
     *          b: bool,
     *          u: bool,
     *          r: bool,
     *          g: bool,
     *          w: bool,
     *          c: bool,
     *          pointsBaseSingleton: float|null,
     *          pointsBaseQuadruples: float|null,
     *          pointsDuelCommander: float|null,
     *          pointsDuelCommanderSpecial: float|null,
     *          pointsCommander: float|null,
     *          pointsCommanderSpecial: float|null,
     *          pointsHighlander: float|null,
     *          pointsModern: float|null,
     *          pointsPioneer: float|null,
     *          pointsStandard: float|null
     *     },
     *     pvalues: array{
     *         baseSingletonStandardPlay: float|null,
     *         baseQuadruplesStandardPlay: float|null,
     *         duelCommanderStandardPlay: float|null,
     *         commanderStandardPlay: float|null,
     *         highlanderStandardPlay: float|null,
     *         modernStandardPlay: float|null,
     *         pioneerStandardPlay: float|null,
     *         standardStandardPlay: float|null,
     *         baseSingletonLitePlay: float|null,
     *         baseQuadruplesLitePlay: float|null,
     *         duelCommanderLitePlay: float|null,
     *         commanderLitePlay: float|null,
     *         highlanderLitePlay: float|null,
     *         modernLitePlay: float|null,
     *         pioneerLitePlay: float|null,
     *         standardLitePlay: float|null,
     *         baseSingletonPowerPlay: float|null,
     *         baseQuadruplesPowerPlay: float|null,
     *         duelCommanderPowerPlay: float|null,
     *         commanderPowerPlay: float|null,
     *         highlanderPowerPlay: float|null,
     *         modernPowerPlay: float|null,
     *         pioneerPowerPlay: float|null,
     *         standardPowerPlay: float|null
     *     },
     *     calculatorJsFunctionPrefix: string,
     *     timelineprecedences: array {
     *         string, int
     *     }
     * }
     */
    #[Override]
    public function mergeMTGSourceAndPointsListAsArray(SourceItemsRepositoryInterface $entityRepository, PointsListInterface $pointsList): array
    {
        /** @var array<int, MTGSourceCard> $sourceCards */
        $sourceCards = $entityRepository->getAllItemsAsArray();
        /** @var array<int, MTGPointsListCard> $pointListCards */
        $pointListCards = $pointsList->getItems();

        $pointsListCardsArray = [
            'cards'                      => [],
            'unranked'                   => [],
            'timelineprecedences'        => self::TIMELINE_PRECEDENCES,
            'calculatorJsFunctionPrefix' => self::CALCULATOR_JS_FUNCTION_PREFIX,
            'pvalues'                    => [
                'baseSingletonStandardPlay'  => $pointsList->getPValueBaseSingletonStandardPlay(),
                'baseQuadruplesStandardPlay' => $pointsList->getPValueBaseQuadruplesStandardPlay(),
                'duelCommanderStandardPlay'  => $pointsList->getPValueDuelCommanderStandardPlay(),
                'commanderStandardPlay'      => $pointsList->getPValueCommanderStandardPlay(),
                'highlanderStandardPlay'     => $pointsList->getPValueHighlanderStandardPlay(),
                'modernStandardPlay'         => $pointsList->getPValueModernStandardPlay(),
                'pioneerStandardPlay'        => $pointsList->getPValuePioneerStandardPlay(),
                'standardStandardPlay'       => $pointsList->getPValueStandardStandardPlay(),
                'baseSingletonLitePlay'      => $pointsList->getPValueBaseSingletonLitePlay(),
                'baseQuadruplesLitePlay'     => $pointsList->getPValueBaseQuadruplesLitePlay(),
                'duelCommanderLitePlay'      => $pointsList->getPValueDuelCommanderLitePlay(),
                'commanderLitePlay'          => $pointsList->getPValueCommanderLitePlay(),
                'highlanderLitePlay'         => $pointsList->getPValueHighlanderLitePlay(),
                'modernLitePlay'             => $pointsList->getPValueModernLitePlay(),
                'pioneerLitePlay'            => $pointsList->getPValuePioneerLitePlay(),
                'standardLitePlay'           => $pointsList->getPValueStandardLitePlay(),
                'baseSingletonPowerPlay'     => $pointsList->getPValueBaseSingletonPowerPlay(),
                'baseQuadruplesPowerPlay'    => $pointsList->getPValueBaseQuadruplesPowerPlay(),
                'duelCommanderPowerPlay'     => $pointsList->getPValueDuelCommanderPowerPlay(),
                'commanderPowerPlay'         => $pointsList->getPValueCommanderPowerPlay(),
                'highlanderPowerPlay'        => $pointsList->getPValueHighlanderPowerPlay(),
                'modernPowerPlay'            => $pointsList->getPValueModernPowerPlay(),
                'pioneerPowerPlay'           => $pointsList->getPValuePioneerPowerPlay(),
                'standardPowerPlay'          => $pointsList->getPValueStandardPowerPlay(),
            ],
        ];

        // First, transform all source cards into an array for serialization
        foreach ($sourceCards as $sourceCard) {
            $pointsListCardsArray['cards'][$sourceCard->getNameEN()] = [
                'flavorofnameen'   => $sourceCard->getFlavorOfNameEN(),
                'alternatenameen'  => $sourceCard->getAlternateNameEN(),
                'imageurl'         => $sourceCard->getImageURL(),
                'mv'               => $sourceCard->getManaValue(),
                'multicztype'      => $sourceCard->getMultiCZType(),
                'ci'               => $sourceCard->getColorIdentity(),
                'timeline'         => $sourceCard->getMaximumTimelineLegality(),
                'mvalue'           => $sourceCard->getMValueAsFloat(),
                'tix'              => $sourceCard->getMTGOPriceAsFloat(),
                'firstprintedyear' => $sourceCard->getFirstPrintedYear(),
                'firstprintedon'   => $sourceCard->getFirstPrintedAt()->getTimestamp(),
                'legal2HG'         => $sourceCard->isLegal2HG(),
                'legal2HGSpecial'  => $sourceCard->isLegal2HGSpecial(),
                'legalDC'          => $sourceCard->isLegalDuelCommander(),
                'legalDCSpecial'   => $sourceCard->isLegalDuelCommanderSpecial(),
                'legalCEDH'        => $sourceCard->isLegalCommander(),
                'legalCEDHSpecial' => $sourceCard->isLegalCommanderSpecial(),
                'czeligible'       => $sourceCard->isCommandZoneEligible(),
                'multiczeligible'  => $sourceCard->isMultipleCommandZoneEligible(),
                'maxcopies'        => $sourceCard->getMaxCopies(),
                'b'                => $sourceCard->isBlack(),
                'u'                => $sourceCard->isBlue(),
                'r'                => $sourceCard->isRed(),
                'g'                => $sourceCard->isGreen(),
                'w'                => $sourceCard->isWhite(),
                'c'                => $sourceCard->isColorless(),
                'mtgtop8rank'      => $sourceCard->getDuelRank(),
                'ffarank'          => $sourceCard->getFFARank(),
                'cedhrank'         => $sourceCard->getCEDHRank(),
            ];

            if ($sourceCard->isABackground() === true) {
                $pointsListCardsArray['cards'][$sourceCard->getNameEN()]['types'] = $sourceCard->getTypes();
                $pointsListCardsArray['cards'][$sourceCard->getNameEN()]['isbg'] = true;
            }

            if ($sourceCard->hasChooseABackground() === true) {
                $pointsListCardsArray['cards'][$sourceCard->getNameEN()]['hasbg'] = true;
            }

            if ($sourceCard->isADoctor() === true) {
                $pointsListCardsArray['cards'][$sourceCard->getNameEN()]['isdoctor'] = true;
            }

            if ($sourceCard->hasDoctorsCompanion() === true) {
                $pointsListCardsArray['cards'][$sourceCard->getNameEN()]['hasdoctor'] = true;
            }
        }

        // Then, add points list values to each identified card
        foreach ($pointListCards as $pointListCard) {

            if ($pointListCard->getNameEN() === self::UNRANKED_CARD_NAME) {
                $pointsListCardsArray['unranked'] = [
                    'flavorofnameen'             => null,
                    'alternatenameen'            => '',
                    'imageurl'                   => '',
                    'mv'                         => 0.0,
                    'multicztype'                => '',
                    'ci'                         => [],
                    'timeline'                   => 'printed',
                    'mvalue'                     => 0.0,
                    'tix'                        => 0.0,
                    'firstprintedyear'           => 10000,
                    'firstprintedon'             => 10000000000000,
                    'legal2HG'                   => true,
                    'legal2HGSpecial'            => true,
                    'legalDC'                    => true,
                    'legalDCSpecial'             => true,
                    'legalCEDH'                  => true,
                    'legalCEDHSpecial'           => true,
                    'czeligible'                 => true,
                    'multiczeligible'            => true,
                    'b'                          => false,
                    'u'                          => false,
                    'r'                          => false,
                    'g'                          => false,
                    'w'                          => false,
                    'c'                          => false,
                    'pointsBaseSingleton'        => $pointListCard->getPointsBaseSingleton(),
                    'pointsBaseQuadruples'       => $pointListCard->getPointsBaseQuadruples(),
                    'pointsDuelCommander'        => $pointListCard->getPointsDuelCommander(),
                    'pointsDuelCommanderSpecial' => $pointListCard->getPointsDuelCommanderSpecial(),
                    // 'points2HG' => $pointListCard->getPoints2HG(),
                    // 'points2HGSpecial' => $pointListCard->getPoints2HGSpecial(),
                    'pointsCommander'            => $pointListCard->getPointsCommander(),
                    'pointsCommanderSpecial'     => $pointListCard->getPointsCommanderSpecial(),
                    'pointsHighlander'           => $pointListCard->getPointsHighlander(),
                    'pointsModern'               => $pointListCard->getPointsModern(),
                    'pointsPioneer'              => $pointListCard->getPointsPioneer(),
                    'pointsStandard'             => $pointListCard->getPointsStandard(),
                ];

                continue;
            }

            foreach (array_keys($pointsListCardsArray['cards']) as $sourceCardName) {
                if ($sourceCardName === $pointListCard->getNameEN()) {
                    $pointsListCardsArray['cards'][$sourceCardName]['pointsBaseSingleton'] = $pointListCard->getPointsBaseSingleton();
                    $pointsListCardsArray['cards'][$sourceCardName]['pointsBaseQuadruples'] = $pointListCard->getPointsBaseQuadruples();
                    $pointsListCardsArray['cards'][$sourceCardName]['pointsDuelCommander'] = $pointListCard->getPointsDuelCommander();
                    $pointsListCardsArray['cards'][$sourceCardName]['pointsDuelCommanderSpecial'] = $pointListCard->getPointsDuelCommanderSpecial();
                    // $pointsListCardsArray['cards'][$sourceCardName]['points2HG'] = $pointListCard->getPoints2HG();
                    // $pointsListCardsArray['cards'][$sourceCardName]['points2HGSpecial'] = $pointListCard->getPoints2HGSpecial();
                    $pointsListCardsArray['cards'][$sourceCardName]['pointsCommander'] = $pointListCard->getPointsCommander();
                    $pointsListCardsArray['cards'][$sourceCardName]['pointsCommanderSpecial'] = $pointListCard->getPointsCommanderSpecial();
                    $pointsListCardsArray['cards'][$sourceCardName]['pointsHighlander'] = $pointListCard->getPointsHighlander();
                    $pointsListCardsArray['cards'][$sourceCardName]['pointsModern'] = $pointListCard->getPointsModern();
                    $pointsListCardsArray['cards'][$sourceCardName]['pointsPioneer'] = $pointListCard->getPointsPioneer();
                    $pointsListCardsArray['cards'][$sourceCardName]['pointsStandard'] = $pointListCard->getPointsStandard();

                    continue 2;
                }
            }
        }

        return $pointsListCardsArray;
    }

    /**
     * @param string $csvSourceString
     * @param MTGPointsList $pointsList
     * @param string $filename
     *
     * @return array{
     *     status: string,
     *     message: string
     * }
     */
    #[Override]
    public function processCSVString(string $csvSourceString, PointsListInterface $pointsList, string $filename = ''): array
    {
        $finalResults = [];
        $processingLine = 5;
        /** @var User $currentUser */
        $currentUser = $this->security->getUser();

        // Split the file on new lines
        $splitLines = preg_split('/\\r\\n|\\r|\\n/', mb_trim($csvSourceString));

        // Check if the file contents are empty or not an array
        if (empty($splitLines)) {
            return [
                'status'  => 'error',
                'message' => $this->translator->trans('admin.form.mtg.pointslist.import.error.filecontents'),
            ];
        }

        // Remove the header lines (x5) - discarded as only bearing metadata
        for ($i = 1; $i <= 5; ++$i) {
            array_shift($splitLines);
        }

        // Then process the 3 P-Value lines

        // Standard Play
        ++$processingLine;
        /** @var string $shiftedLineArray */
        $shiftedLineArray = array_shift($splitLines);
        /** @var array<int, string> $CSVlineContentsAsArray */
        $CSVlineContentsAsArray = str_getcsv($shiftedLineArray);

        if (
            count($CSVlineContentsAsArray) < 11
        ) {
            return [
                'status'  => 'error',
                'message' => $this->translator->trans('admin.form.mtg.pointslist.import.error.processing_line', ['line_number' => $processingLine, 'error' => $this->translator->trans('admin.form.mtg.pointslist.import.error.column_count')]),
            ];
        }

        // If we're on the P-Value lines, they MUST contain values for all 3 P-Values.
        if (! isset($CSVlineContentsAsArray[1], $CSVlineContentsAsArray[2], $CSVlineContentsAsArray[3], $CSVlineContentsAsArray[5], $CSVlineContentsAsArray[7], $CSVlineContentsAsArray[8], $CSVlineContentsAsArray[9], $CSVlineContentsAsArray[10])
            || $CSVlineContentsAsArray[1] === '' || ! is_numeric($CSVlineContentsAsArray[1]) // Base Singleton
            || $CSVlineContentsAsArray[2] === '' || ! is_numeric($CSVlineContentsAsArray[2]) // Base Quadruples
            || $CSVlineContentsAsArray[3] === '' || ! is_numeric($CSVlineContentsAsArray[3]) // Duel Commander
            || $CSVlineContentsAsArray[5] === '' || ! is_numeric($CSVlineContentsAsArray[5]) // Commander
            || $CSVlineContentsAsArray[7] === '' || ! is_numeric($CSVlineContentsAsArray[7]) // Highlander
            || $CSVlineContentsAsArray[8] === '' || ! is_numeric($CSVlineContentsAsArray[8]) // Modern
            || $CSVlineContentsAsArray[9] === '' || ! is_numeric($CSVlineContentsAsArray[9]) // Pioneer
            || $CSVlineContentsAsArray[10] === '' || ! is_numeric($CSVlineContentsAsArray[10]) // Standard
        ) {
            return [
                'status'  => 'error',
                'message' => $this->translator->trans('admin.form.mtg.pointslist.import.error.processing_line', ['line_number' => $processingLine, 'error' => $this->translator->trans('admin.form.mtg.pointslist.import.error.invalid_first_line')]),
            ];
        }

        $pointsList->setPValueBaseSingletonStandardPlay((float)$CSVlineContentsAsArray[1]);
        $pointsList->setPValueBaseQuadruplesStandardPlay((float)$CSVlineContentsAsArray[2]);
        $pointsList->setPValueDuelCommanderStandardPlay((float)$CSVlineContentsAsArray[3]);
        $pointsList->setPValueCommanderStandardPlay((float)$CSVlineContentsAsArray[5]);
        $pointsList->setPValueHighlanderStandardPlay((float)$CSVlineContentsAsArray[7]);
        $pointsList->setPValueModernStandardPlay((float)$CSVlineContentsAsArray[8]);
        $pointsList->setPValuePioneerStandardPlay((float)$CSVlineContentsAsArray[9]);
        $pointsList->setPValueStandardStandardPlay((float)$CSVlineContentsAsArray[10]);

        // Lite Play
        ++$processingLine;
        /** @var string $shiftedLineArray */
        $shiftedLineArray = array_shift($splitLines);
        /** @var array<int, string> $CSVlineContentsAsArray */
        $CSVlineContentsAsArray = str_getcsv($shiftedLineArray);

        if (
            count($CSVlineContentsAsArray) < 11
        ) {
            return [
                'status'  => 'error',
                'message' => $this->translator->trans('admin.form.mtg.pointslist.import.error.processing_line', ['line_number' => $processingLine, 'error' => $this->translator->trans('admin.form.mtg.pointslist.import.error.column_count')]),
            ];
        }

        // If we're on the P-Value lines, they MUST contain values for all 3 P-Values.
        if (! isset($CSVlineContentsAsArray[1], $CSVlineContentsAsArray[2], $CSVlineContentsAsArray[3], $CSVlineContentsAsArray[5], $CSVlineContentsAsArray[7], $CSVlineContentsAsArray[8], $CSVlineContentsAsArray[9], $CSVlineContentsAsArray[10])
            || $CSVlineContentsAsArray[1] === '' || ! is_numeric($CSVlineContentsAsArray[1]) // Base Singleton
            || $CSVlineContentsAsArray[2] === '' || ! is_numeric($CSVlineContentsAsArray[2]) // Base Quadruples
            || $CSVlineContentsAsArray[3] === '' || ! is_numeric($CSVlineContentsAsArray[3]) // Duel Commander
            || $CSVlineContentsAsArray[5] === '' || ! is_numeric($CSVlineContentsAsArray[5]) // Commander
            || $CSVlineContentsAsArray[7] === '' || ! is_numeric($CSVlineContentsAsArray[7]) // Highlander
            || $CSVlineContentsAsArray[8] === '' || ! is_numeric($CSVlineContentsAsArray[8]) // Modern
            || $CSVlineContentsAsArray[9] === '' || ! is_numeric($CSVlineContentsAsArray[9]) // Pioneer
            || $CSVlineContentsAsArray[10] === '' || ! is_numeric($CSVlineContentsAsArray[10]) // Standard
        ) {
            return [
                'status'  => 'error',
                'message' => $this->translator->trans('admin.form.mtg.pointslist.import.error.processing_line', ['line_number' => $processingLine, 'error' => $this->translator->trans('admin.form.mtg.pointslist.import.error.invalid_first_line')]),
            ];
        }

        $pointsList->setPValueBaseSingletonLitePlay((float)$CSVlineContentsAsArray[1]);
        $pointsList->setPValueBaseQuadruplesLitePlay((float)$CSVlineContentsAsArray[2]);
        $pointsList->setPValueDuelCommanderLitePlay((float)$CSVlineContentsAsArray[3]);
        $pointsList->setPValueCommanderLitePlay((float)$CSVlineContentsAsArray[5]);
        $pointsList->setPValueHighlanderLitePlay((float)$CSVlineContentsAsArray[7]);
        $pointsList->setPValueModernLitePlay((float)$CSVlineContentsAsArray[8]);
        $pointsList->setPValuePioneerLitePlay((float)$CSVlineContentsAsArray[9]);
        $pointsList->setPValueStandardLitePlay((float)$CSVlineContentsAsArray[10]);

        // Power Play
        ++$processingLine;
        /** @var string $shiftedLineArray */
        $shiftedLineArray = array_shift($splitLines);
        /** @var array<int, string> $CSVlineContentsAsArray */
        $CSVlineContentsAsArray = str_getcsv($shiftedLineArray);

        if (
            count($CSVlineContentsAsArray) < 11
        ) {
            return [
                'status'  => 'error',
                'message' => $this->translator->trans('admin.form.mtg.pointslist.import.error.processing_line', ['line_number' => $processingLine, 'error' => $this->translator->trans('admin.form.mtg.pointslist.import.error.column_count')]),
            ];
        }

        // If we're on the P-Value lines, they MUST contain values for all 3 P-Values.
        if (! isset($CSVlineContentsAsArray[1], $CSVlineContentsAsArray[2], $CSVlineContentsAsArray[3], $CSVlineContentsAsArray[5], $CSVlineContentsAsArray[7], $CSVlineContentsAsArray[8], $CSVlineContentsAsArray[9], $CSVlineContentsAsArray[10])
            || $CSVlineContentsAsArray[1] === '' || ! is_numeric($CSVlineContentsAsArray[1]) // Base Singleton
            || $CSVlineContentsAsArray[2] === '' || ! is_numeric($CSVlineContentsAsArray[2]) // Base Quadruples
            || $CSVlineContentsAsArray[3] === '' || ! is_numeric($CSVlineContentsAsArray[3]) // Duel Commander
            || $CSVlineContentsAsArray[5] === '' || ! is_numeric($CSVlineContentsAsArray[5]) // Commander
            || $CSVlineContentsAsArray[7] === '' || ! is_numeric($CSVlineContentsAsArray[7]) // Highlander
            || $CSVlineContentsAsArray[8] === '' || ! is_numeric($CSVlineContentsAsArray[8]) // Modern
            || $CSVlineContentsAsArray[9] === '' || ! is_numeric($CSVlineContentsAsArray[9]) // Pioneer
            || $CSVlineContentsAsArray[10] === '' || ! is_numeric($CSVlineContentsAsArray[10]) // Standard
        ) {
            return [
                'status'  => 'error',
                'message' => $this->translator->trans('admin.form.mtg.pointslist.import.error.processing_line', ['line_number' => $processingLine, 'error' => $this->translator->trans('admin.form.mtg.pointslist.import.error.invalid_first_line')]),
            ];
        }

        $pointsList->setPValueBaseSingletonPowerPlay((float)$CSVlineContentsAsArray[1]);
        $pointsList->setPValueBaseQuadruplesPowerPlay((float)$CSVlineContentsAsArray[2]);
        $pointsList->setPValueDuelCommanderPowerPlay((float)$CSVlineContentsAsArray[3]);
        $pointsList->setPValueCommanderPowerPlay((float)$CSVlineContentsAsArray[5]);
        $pointsList->setPValueHighlanderPowerPlay((float)$CSVlineContentsAsArray[7]);
        $pointsList->setPValueModernPowerPlay((float)$CSVlineContentsAsArray[8]);
        $pointsList->setPValuePioneerPowerPlay((float)$CSVlineContentsAsArray[9]);
        $pointsList->setPValueStandardPowerPlay((float)$CSVlineContentsAsArray[10]);

        $pointsList->setFilename(mb_substr($filename, 0, 254));
        $this->entityManager->persist($pointsList);

        $sourceCards = $this->MTGSourceCardRepository->getAllSourceItemsNamesAsArray();

        // Validate and process each result entry
        while (! empty($splitLines)) {

            ++$processingLine;
            $shiftedLineArray = array_shift($splitLines);
            /** @var array<int, string> $CSVlineContentsAsArray */
            $CSVlineContentsAsArray = str_getcsv((string)$shiftedLineArray);

            // If we're on the first line, it MUST be the unranked cards line.
            if (($processingLine === 9) && isset($CSVlineContentsAsArray[0]) && $CSVlineContentsAsArray[0] !== self::UNRANKED_CARD_NAME) {
                return [
                    'status'  => 'error',
                    'message' => $this->translator->trans('admin.form.mtg.pointslist.import.error.processing_line', ['line_number' => $processingLine, 'error' => $this->translator->trans('admin.form.mtg.pointslist.import.error.invalid_first_line')]),
                ];
            }

            // If the line is empty, skip it
            if (
                count($CSVlineContentsAsArray) <= 1
                || (
                    isset(
                        $CSVlineContentsAsArray[0],
                        $CSVlineContentsAsArray[1],
                        $CSVlineContentsAsArray[2],
                        $CSVlineContentsAsArray[3],
                        $CSVlineContentsAsArray[4],
                        $CSVlineContentsAsArray[5],
                        $CSVlineContentsAsArray[6],
                        $CSVlineContentsAsArray[7],
                        $CSVlineContentsAsArray[8],
                        $CSVlineContentsAsArray[9],
                        $CSVlineContentsAsArray[10]
                    )
                    && empty($CSVlineContentsAsArray[0])
                    && empty($CSVlineContentsAsArray[1])
                    && empty($CSVlineContentsAsArray[2])
                    && empty($CSVlineContentsAsArray[3])
                    && empty($CSVlineContentsAsArray[4])
                    && empty($CSVlineContentsAsArray[5])
                    && empty($CSVlineContentsAsArray[6])
                    && empty($CSVlineContentsAsArray[7])
                    && empty($CSVlineContentsAsArray[8])
                    && empty($CSVlineContentsAsArray[9])
                    && empty($CSVlineContentsAsArray[10])
                )
            ) {
                continue;
            }

            // First, check that the line has the minimum number of columns: the name + at least 10 values in the current model
            // Note: further columns are OK, just discarded
            if (
                count($CSVlineContentsAsArray) < 11
            ) {
                return [
                    'status'  => 'error',
                    'message' => $this->translator->trans('admin.form.mtg.pointslist.import.error.processing_line', ['line_number' => $processingLine, 'error' => $this->translator->trans('admin.form.mtg.pointslist.import.error.column_count')]),
                ];
            }

            // Second, double-check that each line has the correct offsets and types
            if (
                ! isset(
                    $CSVlineContentsAsArray[0],
                    $CSVlineContentsAsArray[1],
                    $CSVlineContentsAsArray[2],
                    $CSVlineContentsAsArray[3],
                    $CSVlineContentsAsArray[4],
                    $CSVlineContentsAsArray[5],
                    $CSVlineContentsAsArray[6],
                    $CSVlineContentsAsArray[7],
                    $CSVlineContentsAsArray[8],
                    $CSVlineContentsAsArray[9],
                    $CSVlineContentsAsArray[10]
                )
            ) {
                return [
                    'status'  => 'error',
                    'message' => $this->translator->trans('admin.form.mtg.pointslist.import.error.processing_line', ['line_number' => $processingLine, 'error' => $this->translator->trans('admin.form.mtg.pointslist.import.error.column_count')]),
                ];
            }

            // Third, check that each line starts with a card name that's valid
            if (! preg_match('/\w+/', $CSVlineContentsAsArray[0])) {
                return [
                    'status'  => 'error',
                    'message' => $this->translator->trans(
                        'admin.form.mtg.pointslist.import.error.processing_line',
                        [
                            'line_number' => $processingLine,
                            'error'       => $this->translator->trans(
                                'admin.form.mtg.pointslist.import.error.incorrectname',
                                ['name' => $CSVlineContentsAsArray[0]]
                            ),
                        ]
                    ),
                ];
            }

            // Fourth, check that each line starts with a card name that's in source cards OR is "((unranked))"
            if ($CSVlineContentsAsArray[0] !== self::UNRANKED_CARD_NAME && ! in_array($CSVlineContentsAsArray[0], $sourceCards, true)) {
                return [
                    'status'  => 'error',
                    'message' => $this->translator->trans(
                        'admin.form.mtg.pointslist.import.error.processing_line',
                        [
                            'line_number' => $processingLine,
                            'error'       => $this->translator->trans(
                                'admin.form.mtg.pointslist.import.error.notfoundinsources',
                                ['name' => $CSVlineContentsAsArray[0]]
                            ),
                        ]
                    ),
                ];
            }

            // Fifth, check that the points columns are either unset/empty or numeric
            if (
                ($CSVlineContentsAsArray[1] !== '' && ! is_numeric($CSVlineContentsAsArray[1]))
                || ($CSVlineContentsAsArray[2] !== '' && ! is_numeric($CSVlineContentsAsArray[2]))
                || ($CSVlineContentsAsArray[3] !== '' && ! is_numeric($CSVlineContentsAsArray[3]))
                || ($CSVlineContentsAsArray[4] !== '' && ! is_numeric($CSVlineContentsAsArray[4]))
                || ($CSVlineContentsAsArray[5] !== '' && ! is_numeric($CSVlineContentsAsArray[5]))
                || ($CSVlineContentsAsArray[6] !== '' && ! is_numeric($CSVlineContentsAsArray[6]))
                || ($CSVlineContentsAsArray[7] !== '' && ! is_numeric($CSVlineContentsAsArray[7]))
                || ($CSVlineContentsAsArray[8] !== '' && ! is_numeric($CSVlineContentsAsArray[8]))
                || ($CSVlineContentsAsArray[9] !== '' && ! is_numeric($CSVlineContentsAsArray[9]))
                || ($CSVlineContentsAsArray[10] !== '' && ! is_numeric($CSVlineContentsAsArray[10]))
            ) {
                return [
                    'status'  => 'error',
                    'message' => $this->translator->trans('admin.form.mtg.pointslist.import.error.processing_line', ['line_number' => $processingLine, 'error' => $this->translator->trans('admin.form.mtg.pointslist.import.error.invalid_game_points')]),
                ];
            }

            // Add the result to the final results array
            // Duel Points, Duel Points as a Commander, 2HG Points, 2HG Points as a Commander, Multi Points, Multi Points as a Commander
            $finalResults[] = [
                'nameEN'                     => $CSVlineContentsAsArray[0],
                'pointsBaseSingleton'        => $CSVlineContentsAsArray[1] !== '' ? (float)$CSVlineContentsAsArray[1] : null,
                'pointsBaseQuadruples'       => $CSVlineContentsAsArray[2] !== '' ? (float)$CSVlineContentsAsArray[2] : null,
                'pointsDuelCommander'        => $CSVlineContentsAsArray[3] !== '' ? (float)$CSVlineContentsAsArray[3] : null,
                'pointsDuelCommanderSpecial' => $CSVlineContentsAsArray[4] !== '' ? (float)$CSVlineContentsAsArray[4] : null,
                'pointsCommander'            => $CSVlineContentsAsArray[5] !== '' ? (float)$CSVlineContentsAsArray[5] : null,
                'pointsCommanderSpecial'     => $CSVlineContentsAsArray[6] !== '' ? (float)$CSVlineContentsAsArray[6] : null,
                'pointsHighlander'           => $CSVlineContentsAsArray[7] !== '' ? (float)$CSVlineContentsAsArray[7] : null,
                'pointsModern'               => $CSVlineContentsAsArray[8] !== '' ? (float)$CSVlineContentsAsArray[8] : null,
                'pointsPioneer'              => $CSVlineContentsAsArray[9] !== '' ? (float)$CSVlineContentsAsArray[9] : null,
                'pointsStandard'             => $CSVlineContentsAsArray[10] !== '' ? (float)$CSVlineContentsAsArray[10] : null,
            ];
        }

        // Clear existing results for the Points List before importing new ones
        $this
            ->entityManager
            ->createQueryBuilder()
            ->delete(MTGPointsListCard::class, 'r')
            ->where('r.pointsList = :points_list')
            ->setParameter('points_list', $pointsList)
            ->getQuery()
            ->execute();

        // Now, persist the new results - should be fine up to thousands of cards
        foreach ($finalResults as $finalResult) {
            $MTGResult = new MTGPointsListCard();
            $MTGResult
                ->setPointsList($pointsList)
                ->setNameEN($this->sanitizeStringFromCSVFile($finalResult['nameEN']))
                ->setPointsBaseSingleton($finalResult['pointsBaseSingleton'])
                ->setPointsBaseQuadruples($finalResult['pointsBaseQuadruples'])
                ->setPointsDuelCommander($finalResult['pointsDuelCommander'])
                ->setPointsDuelCommanderSpecial($finalResult['pointsDuelCommanderSpecial'])
                ->setPointsCommander($finalResult['pointsCommander'])
                ->setPointsCommanderSpecial($finalResult['pointsCommanderSpecial'])
                ->setPointsHighlander($finalResult['pointsHighlander'])
                ->setPointsModern($finalResult['pointsModern'])
                ->setPointsPioneer($finalResult['pointsPioneer'])
                ->setPointsStandard($finalResult['pointsStandard'])
                ->setCreatedBy($currentUser)
                ->setUpdatedBy($currentUser);

            $this->entityManager->persist($MTGResult);
        }

        // Flush all in one shot when no error was detected only
        $this->entityManager->flush();

        return [
            'status'  => 'success',
            'message' => $this->translator->trans('admin.form.mtg.pointslist.import.updated.success', ['number' => ($processingLine - 5)]),
        ];
    }
}
