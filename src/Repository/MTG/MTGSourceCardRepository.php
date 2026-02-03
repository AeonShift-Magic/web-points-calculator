<?php

declare(strict_types = 1);

namespace App\Repository\MTG;

use App\Entity\ItemContractInterface;
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

    #[Override]
    public function getAllItemsAsArray(): array
    {
        /** @var array<int, ItemContractInterface> $sourceCards */
        $sourceCards = $this
            ->getEntityManager()
            ->createQueryBuilder()
            ->select('c')
            ->from(MTGSourceCard::class, 'c')
            ->orderBy('c.nameEN')
            ->getQuery()
            ->getResult();

        return $sourceCards;
    }

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
                ->orderBy('c.nameEN')
                ->getQuery()
                ->getResult(),
            'nameEN'
        );

        return $cardNames;
    }
}
