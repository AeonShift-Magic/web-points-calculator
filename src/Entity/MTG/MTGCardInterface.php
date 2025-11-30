<?php

declare(strict_types = 1);

namespace App\Entity\MTG;

use App\Entity\CardInterface;

interface MTGCardInterface extends CardInterface
{
    public function getMultiCZType(): ?string;

    public function getPoints2HG(): float;

    public function getPoints2HGSpecial(): float;

    public function isLegal2HG(): bool;

    public function isLegal2HGSpecial(): bool;

    public function isLegalDuel(): bool;

    public function isLegalDuelSpecial(): bool;

    public function isLegalMulti(): bool;

    public function isLegalMultiSpecial(): bool;

    public function setIsLegal2HGSpecial(bool $isLegal2HGSpecial): static;

    public function setIsLegalDuel(bool $isLegalDuel): static;

    public function setIsLegalDuelSpecial(bool $isLegalDuelSpecial): static;

    public function setIsLegalMulti(bool $isLegalMulti): static;

    public function setIsLegalMultiSpecial(bool $isLegalMultiSpecial): static;

    public function setIsLegal2HG(bool $isLegal2HG): static;

    public function setMultiCZType(string $multiCZType): static;

    public function setPoints2HG(float $points2HG): static;

    public function setPoints2HGSpecial(float $points2HGSpecial): static;
}
