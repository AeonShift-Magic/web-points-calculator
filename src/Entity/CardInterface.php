<?php

declare(strict_types = 1);

namespace App\Entity;

interface CardInterface extends ItemContractInterface
{
    public int|null $id {
        get;
    }

    public function getNameEN(): string;

    public function getPointsDuel(): float;

    public function getPointsDuelSpecial(): float;

    public function getPointsMulti(): float;

    public function getPointsMultiSpecial(): float;

    public function setNameEN(string $nameEN): static;

    public function setPointsDuel(float $pointsDuel): static;

    public function setPointsDuelSpecial(float $pointsDuelSpecial): static;

    public function setPointsMulti(float $pointsMulti): static;

    public function setPointsMultiSpecial(float $pointsMultiSpecial): static;
}
