<?php

declare(strict_types = 1);

namespace App\Entity\MTG;

use App\Entity\CardInterface;

interface MTGCardInterface extends CardInterface
{
    public int|null $id {
        get;
    }

    public function getMultiCZType(): ?string;

    public function getPointsBaseQuadruples(): ?float;

    public function getPointsBaseSingleton(): ?float;

    public function isLegal2HG(): bool;

    public function isLegal2HGSpecial(): bool;

    public function isLegalCommander(): bool;

    public function isLegalCommanderSpecial(): bool;

    public function isLegalDuelCommander(): bool;

    public function isLegalDuelCommanderSpecial(): bool;

    public function setIsLegal2HG(bool $isLegal2HG): static;

    public function setIsLegal2HGSpecial(bool $isLegal2HGSpecial): static;

    public function setIsLegalCommander(bool $isLegalMulti): static;

    public function setIsLegalCommanderSpecial(bool $isLegalMultiSpecial): static;

    public function setIsLegalDuelCommander(bool $isLegalDuel): static;

    public function setIsLegalDuelCommanderSpecial(bool $isLegalDuelSpecial): static;

    public function setMultiCZType(string $multiCZType): static;
}
