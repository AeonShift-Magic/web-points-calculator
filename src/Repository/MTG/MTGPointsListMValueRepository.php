<?php

declare(strict_types = 1);

namespace App\Repository\MTG;

use App\Entity\MTG\MTGPointsList;
use App\Entity\MTG\MTGPointsListMValue;
use App\Entity\PointsListInterface;
use App\Repository\MValueItemsRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Override;

/**
 * @extends ServiceEntityRepository<MTGPointsListMValue>
 */
class MTGPointsListMValueRepository extends ServiceEntityRepository implements MValueItemsRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MTGPointsListMValue::class);
    }

    public function eraseAllForMTGPointsList(MTGPointsList $pointsList): void
    {
        $this->createQueryBuilder('m')
            ->delete()
            ->where('m.pointsList = :pointslist')
            ->setParameter('pointslist', $pointsList)
            ->getQuery()
            ->execute();
    }

    /**
     * @param PointsListInterface|null $pointsList the facultative points list to filter the results by
     *
     * @return array<int, MTGPointsListMValue>
     */
    #[Override]
    public function getAllItemsAsArray(?PointsListInterface $pointsList = null): array
    {
        $sourceCards = $this
            ->getEntityManager()
            ->createQueryBuilder()
            ->select('c')
            ->from(MTGPointsListMValue::class, 'c')
            ->orderBy('c.nameEN');

        if ($pointsList !== null) {
            $sourceCards
                ->andWhere('c.pointsList = :pointslist')
                ->setParameter('pointslist', $pointsList->id);
        }

        /** @var array<int, MTGPointsListMValue> $results */
        $results = $sourceCards
            ->getQuery()
            ->getResult();

        return $results;
    }

    /**
     * @return array<int, string>
     */
    #[Override]
    public function getAllSourceItemsNamesAsArray(?PointsListInterface $pointsList = null): array
    {
        $sourceCards = $this
            ->getEntityManager()
            ->createQueryBuilder()
            ->select('c.nameEN')
            ->from(MTGPointsListMValue::class, 'c')
            ->orderBy('c.nameEN');

        if ($pointsList !== null) {
            $sourceCards
                ->andWhere('c.pointsList = :pointslist')
                ->setParameter('pointslist', $pointsList->id);
        }

        /** @var array<int, MTGPointsListMValue> $results */
        $results = $sourceCards
            ->getQuery()
            ->getResult();

        /** @var array<int, string> $cardNames */
        $cardNames = array_column(
            $results,
            'nameEN'
        );

        return $cardNames;
    }
}
