<?php

declare(strict_types = 1);

namespace App\Entity;

use DateTime;

interface UpdateInterface
{
    public int|null $id {
        get;
    }

    public function getDescriptionEN(): ?string;

    public function getEndingAt(): ?DateTime;

    public function getStartingAt(): ?DateTime;

    public function getTitleEN(): string;

    public function getTitleForForms(): string;

    public function setEndingAt(DateTime $endingAt): static;

    public function setStartingAt(DateTime $startingAt): static;
}
