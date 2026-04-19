<?php

declare(strict_types = 1);

namespace App\Repository;

use App\Entity\ItemContractInterface;
use App\Entity\PointsListInterface;

interface MValueItemsRepositoryInterface
{
    /**
     * @return array<int, ItemContractInterface>
     */
    public function getAllItemsAsArray(?PointsListInterface $pointsList = null): array;

    /**
     * @return array<int, string>
     */
    public function getAllSourceItemsNamesAsArray(?PointsListInterface $pointsList = null): array;
}
