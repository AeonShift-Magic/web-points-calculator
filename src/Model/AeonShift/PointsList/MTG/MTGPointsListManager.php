<?php

declare(strict_types = 1);

namespace App\Model\AeonShift\PointsList\MTG;

use App\Entity\MTG\MTGUpdate;
use App\Model\AeonShift\PointsList\PointsListModelInterface;
use App\Repository\MTG\MTGSourceCardRepository;
use App\Repository\MTG\MTGUpdateRepository;
use Doctrine\ORM\EntityManagerInterface;
use const JSON_THROW_ON_ERROR;
use const JSON_UNESCAPED_UNICODE;
use JsonException;
use Psr\Cache\InvalidArgumentException;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final class MTGPointsListManager
{
    /** @var string self-identifier for model grouping per license */
    public const string LICENSE = 'MTG';

    public function __construct(
        private MTGUpdateRepository $MTGUpdateRepository,
        private MTGSourceCardRepository $MTGSourceCardRepository,
        private CacheInterface $pool,
        private TranslatorInterface $translator,
        private EntityManagerInterface $entityManager,
        private Security $security
    )
    {
    }

    /**
     * Outputs a JSON string containing all published MTG Updates and their Points Lists as JavaScript-compatible output.
     *
     * @throws InvalidArgumentException
     *
     * @return array{
     *     updates: array<int, array{
     *         title: string,
     *         startingAtSimplified: string,
     *         endingAtSimplified: string|null,
     *         startingAtDate: string,
     *         endingAtDate: string,
     *         startingAtTimestamp: int,
     *         endingAtTimestamp: int|null,
     *         pointsList: array{ ... }
     *     }>,
     *     commanders: array{ ... }
     * }
     */
    public function getAllPointListAndUpdatesAsArray(): array
    {
        // @phpstan-ignore-next-line
        return $this->pool->get(
            key: self::LICENSE . '_points_lists',
            callback: /**
             * @throws InvalidArgumentException
             *
             * @return array{
             *     updates: array<int, array{
             *         title: string,
             *         startingAtSimplified: string,
             *         endingAtSimplified: string|null,
             *         startingAtDate: string,
             *         endingAtDate: string,
             *         startingAtTimestamp: int,
             *         endingAtTimestamp: int|null,
             *         pointsList: array{ ... }
             *     }>,
             *     commanders: array{ ... }
             * }
             */
            function (ItemInterface $item): array {
                $item->expiresAfter(3600);

                $MTGUpdates = $this->MTGUpdateRepository->getAllPublishedMTGUpdatesByStartingDate();
                $outputArray = [];
                $count = 1;

                foreach ($MTGUpdates as $MTGUpdate) {
                    $this->processUpdatePointListAsArray($MTGUpdate, $outputArray, $count); // @phpstan-ignore-line
                    ++$count;
                }

                // Also, add the list of potential Command Zones
                $outputArray['commanders'] = $this->MTGSourceCardRepository->getAllCommandersAsArray();

                return $outputArray;
            }
        );
    }

    /**
     * Outputs a JSON string containing all published MTG Updates and their Points Lists as JavaScript-compatible output.
     *
     * @throws InvalidArgumentException|JsonException
     *
     * @return string
     */
    public function getAllPointsListsAndUpdatesAsJSONArray(): string
    {
        return (string)json_encode($this->getAllPointListAndUpdatesAsArray(), JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    }

    /**
     * Outputs a JSON string containing all published MTG Updates and their Command Zone budgets as JavaScript-compatible output.
     *
     * @throws InvalidArgumentException
     *
     * @return array<mixed>
     */
    public function getAllUpdatesAndCommanderPointsAsArray(): array
    {
        return $this->pool->get(key: self::LICENSE . '_point_lists', callback: function (ItemInterface $item): array {
            $item->expiresAfter(3600);

            $MTGUpdates = $this->MTGUpdateRepository->getAllPublishedMTGUpdatesByStartingDate();
            $outputArray = [
                'updates' => [],
            ];
            $count = 1;

            foreach ($MTGUpdates as $MTGUpdate) {
                /** @var class-string $className */
                $className = $MTGUpdate->getPointsList()?->getRulesModel();

                if (class_exists($className) && method_exists($className, 'getName')) {
                    /** @var class-string $rulesModelClassString */
                    $rulesModelClassString = $MTGUpdate->getPointsList()?->getRulesModel();

                    if (class_exists($rulesModelClassString)) {
                        $rulesModel = new $rulesModelClassString(
                            $this->entityManager,
                            $this->translator,
                            $this->MTGSourceCardRepository,
                            $this->security
                        );

                        if ($rulesModel instanceof PointsListModelInterface && method_exists($rulesModel, 'mergeMTGSourceAndPointsListAsArray')) {
                            $outputArray['updates'] = [
                                $MTGUpdate->id => [
                                    'title'                => $count === 1 ? $this->translator->trans('front.mtg.pointslist.latest.label', ['name' => $MTGUpdate->getTitleEN()]) : $this->translator->trans('front.mtg.pointslist.choice.label', ['name' => $MTGUpdate->getTitleEN(), 'datestart' => $MTGUpdate->getStartingAt()->format('Y-m-d h:i'), 'dateend' => $MTGUpdate->getEndingAt()->format('Y-m-d h:i')]),
                                    'startingAtSimplified' => $MTGUpdate->getStartingAt()->format('Y-m-d'),
                                    'endingAtSimplified'   => $count === 1 ? null : $MTGUpdate->getEndingAt()->format('Y-m-d'),
                                    'startingAtDate'       => $MTGUpdate->getStartingAt()->format('Y-m-d\TH:i:s\Z'),
                                    'endingAtDate'         => $MTGUpdate->getEndingAt()->format('Y-m-d\TH:i:s\Z'),
                                    'startingAtTimestamp'  => $MTGUpdate->getStartingAt()->getTimestamp(),
                                    'endingAtTimestamp'    => $count === 1 ? null : $MTGUpdate->getEndingAt()->getTimestamp(),
                                ],
                            ];
                        }
                        ++$count;
                    }
                }
            }

            return $outputArray;
        });
    }

    /**
     * Outputs a JSON string containing all published MTG Updates and their Points Lists as JavaScript-compatible output.
     *
     * @throws InvalidArgumentException|JsonException
     *
     * @return string
     */
    public function getAllUpdatesAndCommanderPointsAsJSONArray(): string
    {
        return (string)json_encode($this->getAllUpdatesAndCommanderPointsAsArray(), JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    }

    /**
     * Outputs a JSON string containing all published MTG Updates and their Points Lists as JavaScript-compatible output.
     *
     * @throws InvalidArgumentException
     *
     * @return array{
     *     updates: array<int, array{
     *         title: string,
     *         startingAtSimplified: string,
     *         endingAtSimplified: string|null,
     *         startingAtDate: string,
     *         endingAtDate: string,
     *         startingAtTimestamp: int,
     *         endingAtTimestamp: int|null,
     *         pointsList: array{ ... }
     *     }>,
     *     commanders: array{ ... }
     * }
     */
    public function getUpdatePointListsAsArray(MTGUpdate $MTGUpdate): array
    {
        // @phpstan-ignore-next-line=
        return $this->pool->get(
            key: self::LICENSE . '_point_list_' . $MTGUpdate->id . '_data',
            callback: /**
             * @throws InvalidArgumentException
             *
             * @return array{
             *     updates: array<int, array{
             *         title: string,
             *         startingAtSimplified: string,
             *         endingAtSimplified: string|null,
             *         startingAtDate: string,
             *         endingAtDate: string,
             *         startingAtTimestamp: int,
             *         endingAtTimestamp: int|null,
             *         pointsList: array{ ... }
             *     }>,
             *     commanders: array{ ... }
             * }
             */
            function (ItemInterface $item) use ($MTGUpdate): array {
                $item->expiresAfter(3600);
                $outputArray = [];
                $this->processUpdatePointListAsArray($MTGUpdate, $outputArray); // @phpstan-ignore-line
                // Also, add the list of potential Command Zones
                $outputArray['commanders'] = $this->MTGSourceCardRepository->getAllCommandersAsArray();

                return $outputArray;
            }
        );
    }

    /**
     * Processes a single MTG Update and its Points List, and adds the data to the given array.
     *
     * @param MTGUpdate $MTGUpdate the source MTG Update to extract data from
     * @param array{
     *     updates: non-empty-array<int, array{
     *         title: string,
     *         startingAtSimplified: non-falsy-string,
     *         endingAtSimplified: non-falsy-string|null,
     *         startingAtDate: non-falsy-string,
     *         endingAtDate: non-falsy-string,
     *         startingAtTimestamp: int,
     *         endingAtTimestamp: int|null,
     *         pointsList: mixed
     *     }>
     * } $dataArray the array to add the data to
     * @param int $count if default (0), will not output anything related to the announcement being the latest
     *
     * @return void
     */
    public function processUpdatePointListAsArray(MTGUpdate $MTGUpdate, array &$dataArray, int $count = 0): void
    {
        /** @var class-string $className */
        $className = $MTGUpdate->getPointsList()?->getRulesModel();

        if (class_exists($className) && method_exists($className, 'getName')) {
            /** @var class-string $rulesModelClassString */
            $rulesModelClassString = $MTGUpdate->getPointsList()?->getRulesModel();

            if (class_exists($rulesModelClassString)) {
                $rulesModel = new $rulesModelClassString(
                    $this->entityManager,
                    $this->translator,
                    $this->MTGSourceCardRepository,
                    $this->security
                );

                if ($rulesModel instanceof PointsListModelInterface && method_exists($rulesModel, 'mergeMTGSourceAndPointsListAsArray')) {
                    /** @var int $updateId */
                    $updateId = $MTGUpdate->id;

                    if (! isset($dataArray['updates'])) { // @phpstan-ignore-line
                        $dataArray['updates'] = []; // @phpstan-ignore-line
                    }

                    $dataArray['updates'][$updateId] = [
                        'title'                => $count === 1 ? $this->translator->trans('front.mtg.pointslist.latest.label', ['name' => $MTGUpdate->getTitleEN()]) : $this->translator->trans('front.mtg.pointslist.choice.label', ['name' => $MTGUpdate->getTitleEN(), 'datestart' => $MTGUpdate->getStartingAt()->format('Y-m-d h:i'), 'dateend' => $MTGUpdate->getEndingAt()->format('Y-m-d h:i')]),
                        'startingAtSimplified' => $MTGUpdate->getStartingAt()->format('Y-m-d'),
                        'endingAtSimplified'   => $count === 1 ? null : $MTGUpdate->getEndingAt()->format('Y-m-d'),
                        'startingAtDate'       => $MTGUpdate->getStartingAt()->format('Y-m-d\TH:i:s\Z'),
                        'endingAtDate'         => $MTGUpdate->getEndingAt()->format('Y-m-d\TH:i:s\Z'),
                        'startingAtTimestamp'  => $MTGUpdate->getStartingAt()->getTimestamp(),
                        'endingAtTimestamp'    => $count === 1 ? null : $MTGUpdate->getEndingAt()->getTimestamp(),
                        'pointsList'           => $rulesModel->mergeMTGSourceAndPointsListAsArray($this->MTGSourceCardRepository, $MTGUpdate->getPointsList()),
                    ];
                }
            }
        }
    }
}
