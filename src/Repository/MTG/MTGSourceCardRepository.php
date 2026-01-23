<?php

declare(strict_types = 1);

namespace App\Repository\MTG;

use App\Entity\ItemContractInterface;
use App\Entity\MTG\MTGSourceCard;
use App\Repository\SourceItemsRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Override;

/**
 * @extends ServiceEntityRepository<MTGSourceCard>
 */
final class MTGSourceCardRepository extends ServiceEntityRepository implements SourceItemsRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MTGSourceCard::class);
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
