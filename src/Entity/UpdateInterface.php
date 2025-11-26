<?php

declare(strict_types = 1);

namespace App\Entity;

use DateTime;

interface UpdateInterface
{
    public function getDescriptionEN(): ?string;

    public function getEndingAt(): ?DateTime;

    public function getId(): ?int;

    public function getStartingAt(): ?DateTime;

    public function getUser(): ?User;

    public function setEndingAt(DateTime $endingAt): static;

    public function setStartingAt(DateTime $startingAt): static;

    public function setUser(?User $user): static;
}
