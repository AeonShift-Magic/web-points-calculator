<?php

declare(strict_types = 1);

namespace App\Entity;

use DateTime;

interface PointsListInterface
{
    public int|null $id {
        get;
    }

    /**
     * @return array<ItemContractInterface>
     */
    public function getItems(): array;

    public function getRulesModel(): string;

    public function getTitle(): string;

    public function getValidityStartingAt(): DateTime;

    public function setRulesModel(string $rulesModel): static;

    public function setTitle(string $title): static;

    public function setValidityStartingAt(DateTime $validityStartingAt): static;
}
