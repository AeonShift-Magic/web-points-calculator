<?php

declare(strict_types = 1);

namespace App\Repository;

interface SourceItemsRepositoryInterface
{
    /**
     * @return array<int, string>
     */
    public function getAllSourceItemsNamesAsArray(): array;
}
