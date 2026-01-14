<?php

/** @noinspection UnnecessaryCastingInspection */

declare(strict_types = 1);

namespace App\Model\AeonShift\PointsList\MTG\V1;

use App\Entity\MTG\MTGPointsList;
use App\Entity\MTG\MTGPointsListCard;
use App\Entity\PointsListInterface;
use App\Entity\User;
use App\Model\AeonShift\Calculator\MTG\AeonShiftMTGCalculator;
use App\Model\AeonShift\PointsList\AbstractPointsListModel;
use App\Repository\SourceItemsRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;
use Override;
use RuntimeException;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Contracts\Translation\TranslatorInterface;

final class MTGPointsListModelV1 extends AbstractPointsListModel
{
    public const string LABEL = 'Initial Points List Model';

    public const string RELEASE_DATE = '2026-01-26';

    public const int VERSION = 1;

    public function __construct(
        private EntityManagerInterface $entityManager,
        private TranslatorInterface $translator,
        private SourceItemsRepositoryInterface $MTGSourceCardRepository,
        private Security $security
    )
    {
    }

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

        $response = new StreamedResponse(static function () use ($pointsListCards): void {
            $handle = fopen('php://output', 'wb');

            if ($handle === false) {
                throw new RuntimeException('Failed to open output stream');
            }

            // Write headers - 5 Lines
            fputcsv(
                $handle,
                [
                    '⚠️ The first 5 Lines of this document will be ignored while importing	↓ Eternal ↓',
                    '↓ Eternal ↓',
                    '',
                    '',
                    '',
                    '',
                    '',
                ]
            );

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
                    ]
                );
            }

            fputcsv(
                $handle,
                [
                    '↓ Card Name ↓',
                    'Duel Points',
                    'Duel Points as a Commander',
                    '2HG Points',
                    '2HG Points as a Commander',
                    'Multi Points',
                    'Multi Points as a Commander',
                ]
            );

            foreach ($pointsListCards as $result) {
                fputcsv(
                    $handle,
                    [
                        $result->getNameEN(),
                        $result->getPointsDuelCommander(),
                        $result->getPointsDuelCommanderSpecial(),
                        $result->getPoints2HG(),
                        $result->getPoints2HGSpecial(),
                        $result->getPointsCommander(),
                        $result->getPointsCommanderSpecial(),
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
     * @param string $csvSourceString
     * @param MTGPointsList $pointsList
     *
     * @return array{
     *     status: string,
     *     message: string
     * }
     */
    #[Override]
    public function processCSVString(string $csvSourceString, PointsListInterface $pointsList): array
    {
        $finalResults = [];
        $processingLine = 0;
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

        $sourceCards = $this->MTGSourceCardRepository->getAllSourceItemsNamesAsArray();

        // Validate and process each result entry
        while (! empty($splitLines)) {

            ++$processingLine;
            $shiftedLineArray = array_shift($splitLines);
            /** @var array<int, string> $CSVlineContentsAsArray */
            $CSVlineContentsAsArray = str_getcsv($shiftedLineArray);

            // If we're on the first line, it MUST be the unranked cards line.
            if (($processingLine === 1) && isset($CSVlineContentsAsArray[0]) && $CSVlineContentsAsArray[0] !== AeonShiftMTGCalculator::UNRANKED_CARD_NAME) {
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
                        $CSVlineContentsAsArray[6]
                    )
                    && empty($CSVlineContentsAsArray[0])
                    && empty($CSVlineContentsAsArray[1])
                    && empty($CSVlineContentsAsArray[2])
                    && empty($CSVlineContentsAsArray[3])
                    && empty($CSVlineContentsAsArray[4])
                    && empty($CSVlineContentsAsArray[5])
                    && empty($CSVlineContentsAsArray[6])
                )
            ) {
                continue;
            }

            // First, check that the line has the minimum number of columns: the name + at least 6 values in the current model
            // Note: further columns are OK, just discarded
            if (
                count($CSVlineContentsAsArray) < 7
            ) {
                return [
                    'status'  => 'error',
                    'message' => $this->translator->trans('admin.form.mtg.pointslist.import.error.processing_line', ['line_number' => $processingLine, 'error' => $this->translator->trans('admin.form.mtg.pointslist.import.error.column_count')]),
                ];
            }

            // Second, double-check that each line has the correct offsets and types
            if (
                ! isset($CSVlineContentsAsArray[0], $CSVlineContentsAsArray[1], $CSVlineContentsAsArray[2], $CSVlineContentsAsArray[3], $CSVlineContentsAsArray[4], $CSVlineContentsAsArray[5], $CSVlineContentsAsArray[6])
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
            if ($CSVlineContentsAsArray[0] !== AeonShiftMTGCalculator::UNRANKED_CARD_NAME && ! in_array($CSVlineContentsAsArray[0], $sourceCards, true)) {
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
            ) {
                return [
                    'status'  => 'error',
                    'message' => $this->translator->trans('admin.form.mtg.pointslist.import.error.processing_line', ['line_number' => $processingLine, 'error' => $this->translator->trans('admin.form.mtg.pointslist.import.error.invalid_game_points')]),
                ];
            }

            // Add the result to the final results array
            // Duel Points, Duel Points as a Commander, 2HG Points, 2HG Points as a Commander, Multi Points, Multi Points as a Commander
            $finalResults[] = [
                'nameEN'                      => $CSVlineContentsAsArray[0],
                'pointsDuelCommander'         => (float)$CSVlineContentsAsArray[1],
                'pointsDuelCommanderSpecial'  => (float)$CSVlineContentsAsArray[2],
                'points2HG'                   => (float)$CSVlineContentsAsArray[3],
                'points2HGSpecial'            => (float)$CSVlineContentsAsArray[4],
                'pointsCommander'             => (float)$CSVlineContentsAsArray[5],
                'pointsCommanderSpecial'      => (float)$CSVlineContentsAsArray[6],
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
                ->setPointsDuelCommander($finalResult['pointsDuelCommander'])
                ->setPointsDuelCommanderSpecial($finalResult['pointsDuelCommanderSpecial'])
                ->setPoints2HG($finalResult['points2HG'])
                ->setPoints2HGSpecial($finalResult['points2HGSpecial'])
                ->setPointsCommander($finalResult['pointsCommander'])
                ->setPointsCommanderSpecial($finalResult['pointsCommanderSpecial'])
                ->setCreatedBy($currentUser)
                ->setUpdatedBy($currentUser);

            $this->entityManager->persist($MTGResult);
        }

        // Flush all in one shot
        $this->entityManager->flush();

        return [
            'status'  => 'success',
            'message' => $this->translator->trans('admin.form.mtg.pointslist.import.updated.success', ['number' => $processingLine]),
        ];
    }
}
