<?php

declare(strict_types = 1);

namespace App\Repository;

use App\Entity\ItemContractInterface;

interface SourceItemsRepositoryInterface
{
    /**
     * @return array<int, ItemContractInterface>
     */
    public function getAllItemsAsArray(): array;

    /**
     * @return array<int, string>
     */
    public function getAllSourceItemsNamesAsArray(): array;
}
