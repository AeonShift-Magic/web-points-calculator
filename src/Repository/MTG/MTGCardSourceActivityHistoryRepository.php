<?php

declare(strict_types = 1);

namespace App\Repository\MTG;

use App\Entity\MTG\MTGCardSourceActivityHistory;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<MTGCardSourceActivityHistory>
 */
final class MTGCardSourceActivityHistoryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MTGCardSourceActivityHistory::class);
    }

    public function getLastDBUpdateActivityHistory(): ?MTGCardSourceActivityHistory
    {
        /** @var ?MTGCardSourceActivityHistory $result */
        $result = $this
            ->createQueryBuilder('c')
            ->andWhere('c.channel LIKE :channelmtg')
            ->setParameter('channelmtg', '%mtg%')
            ->andWhere('c.channel LIKE :channeldbupdate')
            ->setParameter('channeldbupdate', '%dbupdate%')
            ->orderBy('c.createdAt', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        return $result;
    }

    public function getLastDownloadActivityHistory(): ?MTGCardSourceActivityHistory
    {
        /** @var ?MTGCardSourceActivityHistory $result */
        $result = $this
            ->createQueryBuilder('c')
            ->andWhere('c.channel LIKE :channelmtg')
            ->setParameter('channelmtg', '%mtg%')
            ->andWhere('c.channel LIKE :channeldownload')
            ->setParameter('channeldownload', '%download%')
            ->orderBy('c.createdAt', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        return $result;
    }
}
