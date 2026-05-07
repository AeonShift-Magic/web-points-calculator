<?php

declare(strict_types = 1);

namespace App\Repository\MTG;

use App\Entity\MTG\MTGUpdate;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Cache\InvalidArgumentException;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

/**
 * @extends ServiceEntityRepository<MTGUpdate>
 */
final class MTGUpdateRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry, private CacheInterface $pool)
    {
        parent::__construct($registry, MTGUpdate::class);
    }

    /**
     * @throws InvalidArgumentException
     *
     * @return MTGUpdate[]
     */
    public function getAllPublishedMTGUpdatesByStartingDate(): array
    {
        return $this->pool->get(key: 'mtg_published_updates_by_starting_date', callback: function (ItemInterface $item): array {
            $item->expiresAfter(9500);

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
                ->setHint(Query::HINT_READ_ONLY, true)
                ->getResult();

            return $result;
        });
    }

    /**
     * @throws InvalidArgumentException
     *
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
