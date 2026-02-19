<?php

declare(strict_types = 1);

namespace App\Repository\MTG;

use App\Entity\MTG\MTGSourceCard;
use App\Repository\SourceItemsRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use const JSON_THROW_ON_ERROR;
use const JSON_UNESCAPED_UNICODE;
use Override;
use Psr\Cache\InvalidArgumentException;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

/**
 * @extends ServiceEntityRepository<MTGSourceCard>
 */
final class MTGSourceCardRepository extends ServiceEntityRepository implements SourceItemsRepositoryInterface
{
    public function __construct(ManagerRegistry $registry, private CacheInterface $pool)
    {
        parent::__construct($registry, MTGSourceCard::class);
    }

    /**
     * Returns a structure that contains all the command zone.
     *
     * @throws InvalidArgumentException
     *
     * @return string
     */
    public function getAllCardNamesCached(): string
    {
        return $this->pool->get(key: 'mtg_cards_by_name', callback: function (ItemInterface $item): string {
            $item->expiresAfter(10000);
            $cards = $this->getAllSourceItemsNamesAsArray();

            return (string)json_encode($cards, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        });
    }

    /**
     * Returns all Commanders.
     *
     * @throws InvalidArgumentException
     *
     * @return array<int, MTGSourceCard>
     */
    public function getAllCommanders(): array
    {
        return $this->pool->get(key: 'mtg_commanders', callback: function (ItemInterface $item): array {
            $item->expiresAfter(10000);
            /** @var array<int, MTGSourceCard> $sourceCommanderCards */
            $sourceCommanderCards = $this
                ->getEntityManager()
                ->createQueryBuilder()
                ->select('c')
                ->from(MTGSourceCard::class, 'c')
                ->andWhere('c.isCommandZoneEligible = :eligible')
                ->andWhere('c.flavorOfNameEN = :empty')
                ->andWhere('c.isDigitalOnly = :false')
                ->setParameter('false', false)
                ->setParameter('eligible', true)
                ->setParameter('empty', '')
                ->orderBy('c.nameEN', 'ASC')
                ->getQuery()
                ->getResult();

            return $sourceCommanderCards;
        });
    }

    /**
     * Returns all Commanders.
     *
     * @throws InvalidArgumentException
     *
     * @return array<string, array{
     *     nameen: string,
     *     imageurl: string|null,
     *     mv: float,
     *     multicztype: string,
     *     ci: array<int, string>,
     *     timeline: string,
     *     mvalue: float,
     *     firstprintedyear: int,
     *     firstprintedon: int,
     *     legal2HG: bool,
     *     legal2HGSpecial: bool,
     *     legalDC: bool,
     *     legalDCSpecial: bool,
     *     legalCEDH: bool,
     *     legalCEDHSpecial: bool,
     *     czeligible: bool,
     *     multiczeligible: bool,
     *     b: bool,
     *     u: bool,
     *     r: bool,
     *     g: bool,
     *     w: bool,
     *     c: bool
     * }>
     */
    public function getAllCommandersAsArray(): array
    {
        $sourceCommanderCards = $this->getAllCommanders();
        $commandersArray = [];

        foreach ($sourceCommanderCards as $sourceCommanderCard) {
            $nameEn = $sourceCommanderCard->getNameEN();
            $commandersArray[$nameEn] = [
                'nameen'           => $nameEn,
                // 'flavorofnameen'             => $sourceCommanderCard->getFlavorOfNameEN(),
                // 'alternatenameen'            => $sourceCommanderCard->getAlternateNameEN(),
                'imageurl'         => $sourceCommanderCard->getImageURL(),
                'mv'               => $sourceCommanderCard->getManaValue(),
                'multicztype'      => $sourceCommanderCard->getMultiCZType(),
                'ci'               => $sourceCommanderCard->getColorIdentity(),
                'timeline'         => $sourceCommanderCard->getMaximumTimelineLegality(),
                'mvalue'           => $sourceCommanderCard->getMValueAsFloat(),
                // 'tix'                        => $sourceCommanderCard->getMTGOPriceAsFloat(),
                'firstprintedyear' => $sourceCommanderCard->getFirstPrintedYear(),
                'firstprintedon'   => $sourceCommanderCard->getFirstPrintedAt()->getTimestamp(),
                'legal2HG'         => $sourceCommanderCard->isLegal2HG(),
                'legal2HGSpecial'  => $sourceCommanderCard->isLegal2HGSpecial(),
                'legalDC'          => $sourceCommanderCard->isLegalDuelCommander(),
                'legalDCSpecial'   => $sourceCommanderCard->isLegalDuelCommanderSpecial(),
                'legalCEDH'        => $sourceCommanderCard->isLegalCommander(),
                'legalCEDHSpecial' => $sourceCommanderCard->isLegalCommanderSpecial(),
                'czeligible'       => $sourceCommanderCard->isCommandZoneEligible(),
                'multiczeligible'  => $sourceCommanderCard->isMultipleCommandZoneEligible(),
                'b'                => $sourceCommanderCard->isBlack(),
                'u'                => $sourceCommanderCard->isBlue(),
                'r'                => $sourceCommanderCard->isRed(),
                'g'                => $sourceCommanderCard->isGreen(),
                'w'                => $sourceCommanderCard->isWhite(),
                'c'                => $sourceCommanderCard->isColorless(),
            ];
        }

        return $commandersArray;
    }

    /**
     * @return array<int, MTGSourceCard>
     */
    #[Override]
    public function getAllItemsAsArray(): array
    {
        /** @var array<int, MTGSourceCard> $sourceCards */
        $sourceCards = $this
            ->getEntityManager()
            ->createQueryBuilder()
            ->select('c')
            ->from(MTGSourceCard::class, 'c')
            ->where('c.isDigitalOnly = :false')
            ->setParameter('false', false)
            ->orderBy('c.nameEN')
            ->getQuery()
            ->getResult();

        return $sourceCards;
    }

    /**
     * @return array<int, string>
     */
    #[Override]
    public function getAllSourceItemsNamesAsArray(): array
    {
        /** @var array<int, string> $cardNames */
        $cardNames = array_column(
            $this
                ->getEntityManager()
                ->createQueryBuilder()
                ->select('c.nameEN')
                ->from(MTGSourceCard::class, 'c')
                ->where('c.isDigitalOnly = :false')
                ->setParameter('false', false)
                ->orderBy('c.nameEN')
                ->getQuery()
                ->getResult(),
            'nameEN'
        );

        return $cardNames;
    }

    /**
     * Get an array with the count of all cards, cEDH commanders and FFA commanders.
     *
     * @throws InvalidArgumentException
     *
     * @return array{
     *     ffa: string,
     *     duel: string,
     *     cedh: string
     * }
     */
    public function getRankingTotals(): array
    {
        return $this->pool->get(key: 'mtg_ranking_totals', callback: function (ItemInterface $item): array {
            $item->expiresAfter(10000);

            $qb = $this->getEntityManager()->createQueryBuilder();
            $qb
                ->select(
                    'SUM(CASE WHEN c.FFARank <> 0 THEN 1 ELSE 0 END) AS ffa',
                    'SUM(CASE WHEN c.duelRank <> 0 THEN 1 ELSE 0 END) AS duel',
                    'SUM(CASE WHEN c.CEDHRank <> 0 THEN 1 ELSE 0 END) AS cedh'
                )
                ->from(MTGSourceCard::class, 'c');

            /** @var array{ffa: string, duel: string, cedh: string} $rankings */
            $rankings = $qb->getQuery()->getSingleResult();

            return $rankings;
        });
    }
}
