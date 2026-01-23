<?php

declare(strict_types = 1);

namespace App\Entity;

/**
 * When calculating the points for a card, a precedence system is used.
 * 1. Format-specific points are used, if not null.
 * 2. Timeline-specific points are used, if not null.
 * 3. Default points are used, depending on the number of copies of a card in a deck and the fact it uses Command Zones or not.
 */
interface CardInterface extends ItemContractInterface
{
    public int|null $id {
        get;
    }

    /**
     * @return string the English name of the card
     */
    public function getNameEN(): string;

    /**
     * @return float|null the default points for a single card when played in a quadruples format
     */
    public function getPointsBaseQuadruples(): ?float;

    /**
     * @return float|null the default points for a single card when played in a singleton format
     */
    public function getPointsBaseSingleton(): ?float;

    /**
     * @return float|null overriden points for Multiplayer formats (FFA)
     */
    public function getPointsCommander(): ?float;

    /**
     * @return float|null Overriden special points for Multiplayer formats (FFA), i.e. as a Commander.
     */
    public function getPointsCommanderSpecial(): ?float;

    /**
     * @return float|null overriden points for Duel Commander
     */
    public function getPointsDuelCommander(): ?float;

    /**
     * @return float|null Overriden special points for Duel Commander, i.e. as a Commander.
     */
    public function getPointsDuelCommanderSpecial(): ?float;

    /**
     * @param string $nameEN the English name of the card
     *
     * @return $this
     */
    public function setNameEN(string $nameEN): static;

    /**
     * @param float|null $pointsBaseQuadruples the default points for a single card when played in a quadruples format
     *
     * @return static
     */
    public function setPointsBaseQuadruples(?float $pointsBaseQuadruples): self;

    /**
     * @param float|null $pointsBaseSingleton the default points for a single card when played in a singleton format
     *
     * @return static
     */
    public function setPointsBaseSingleton(?float $pointsBaseSingleton): self;

    /**
     * @param float|null $pointsCommander
     *
     * @return static overriden points for Multiplayer formats (FFA)
     */
    public function setPointsCommander(?float $pointsCommander): self;

    /**
     * @param float|null $pointsCommanderSpecial
     *
     * @return static Overriden special points for Multiplayer formats (FFA), i.e. as a Commander.
     */
    public function setPointsCommanderSpecial(?float $pointsCommanderSpecial): self;

    /**
     * @param float|null $pointsDuelCommander overriden points for Duel Commander
     *
     * @return $this
     */
    public function setPointsDuelCommander(?float $pointsDuelCommander): self;

    /**
     * @param float|null $pointsDuelCommanderSpecial Overriden special points for Duel Commander, i.e. as a Commander.
     *
     * @return $this
     */
    public function setPointsDuelCommanderSpecial(?float $pointsDuelCommanderSpecial): self;
}
