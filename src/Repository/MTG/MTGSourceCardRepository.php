<?php

declare(strict_types = 1);

namespace App\Repository\MTG;

use App\Entity\MTG\MTGSourceCard;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<MTGSourceCard>
 */
final class MTGSourceCardRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MTGSourceCard::class);
    }

    /**
     * @return array<int, string>
     */
    public function getAllSourceCardNamesAsArray(): array
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
                ->getArrayResult(),
            'nameEN'
        );

        return $cardNames;
    }
}
