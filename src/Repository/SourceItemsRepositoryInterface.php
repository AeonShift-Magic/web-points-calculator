<?php

declare(strict_types = 1);

namespace App\Repository;

interface SourceItemsRepositoryInterface
{
    /**
     * @return array<int, array<string, float|int|string|null>>
     */
    public function getAllItemsAsArray(): array;

    /**
     * @return array<int, string>
     */
    public function getAllSourceItemsNamesAsArray(): array;
}
