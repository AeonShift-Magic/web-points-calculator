<?php

declare(strict_types = 1);

namespace App\Repository\MTG;

use App\Entity\MTG\MTGUpdate;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<MTGUpdate>
 */
final class MTGUpdateRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MTGUpdate::class);
    }

    /**
     * @return MTGUpdate[]
     */
    public function getAllPublishedMTGUpdatesByStartingDate(): array
    {
        /** @var MTGUpdate[] $result */
        $result = $this
            ->createQueryBuilder('u')
            ->andWhere('u.isPublic LIKE :isPublic')
            ->setParameter('isPublic', true)
            ->innerJoin('u.pointsList', 'p')
            ->addSelect('p')
            ->join('p.MTGPointListCards', 'c')
            ->addSelect('c')
            ->orderBy('u.startingAt', 'DESC')
            ->getQuery()
            ->getResult();

        return $result;
    }

    /**
     * @return array<string, int>
     */
    public function getAllPublishedMTGUpdatesByStartingDateForForms(): array
    {
        $updates = $this->getAllPublishedMTGUpdatesByStartingDate();
        $formEntries = [];

        foreach ($updates as $update) {
            if ($update->id !== null) {
                $formEntries[$update->getTitleForForms()] = $update->id;
            }
        }

        return $formEntries;
    }
}
