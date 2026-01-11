<?php

declare(strict_types = 1);

namespace App\Entity\MTG;

use App\Entity\HistoryTrackableEntityTrait;
use Doctrine\ORM\Mapping as ORM;
use Override;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Abstract base class representing a Magic: The Gathering card entity with lifecycle callbacks.
 *
 * - The legalities "special" ar so far used for Command Zones only (Commander usage).
 * - The colors are based on color identity, not on card colors.
 * - The term "multi" refers to FFA 3+ players (Commander, cEDH, etc.).
 */
#[ORM\HasLifecycleCallbacks]
#[ORM\MappedSuperclass]
abstract class MTGAbstractCard implements MTGCardInterface
{
    use HistoryTrackableEntityTrait {
        HistoryTrackableEntityTrait::__construct as private __traitConstruct;
    }

    #[ORM\Column]
    #[ORM\GeneratedValue]
    #[ORM\Id]
    public ?int $id = null {
        get {
            return $this->id;
        }
    }

    #[Assert\NotNull]
    #[ORM\Column(name: 'is_legal_2hg')]
    private bool $isLegal2HG = false;

    #[Assert\NotNull]
    #[ORM\Column(name: 'is_legal_2hg_special')]
    private bool $isLegal2HGSpecial = false;

    #[Assert\NotNull]
    #[ORM\Column]
    private bool $isLegalDuel = false;

    #[Assert\NotNull]
    #[ORM\Column]
    private bool $isLegalDuelSpecial = false;

    #[Assert\NotNull]
    #[ORM\Column]
    private bool $isLegalMulti = false;

    #[Assert\NotNull]
    #[ORM\Column]
    private bool $isLegalMultiSpecial = false;

    #[Assert\NotNull]
    #[ORM\Column(type: 'float')]
    private float $manaValue = 0.0;

    #[Assert\Length(max: 255)]
    #[Assert\NotNull]
    #[ORM\Column(name: 'multi_cz_type', length: 255)]
    private string $multiCZType = '';

    #[Assert\Length(max: 255)]
    #[Assert\NotNull]
    #[ORM\Column(length: 255)]
    private string $nameEN = '';

    #[Assert\Uuid]
    #[ORM\Column(type: 'guid', nullable: true)]
    private ?string $oracleId = null;

    #[Assert\NotNull]
    #[ORM\Column(name: 'points_2hg', type: 'float')]
    private float $points2HG = 0.0;

    #[Assert\NotNull]
    #[ORM\Column(name: 'points_2hg_special', type: 'float')]
    private float $points2HGSpecial = 0.0;

    #[Assert\NotNull]
    #[ORM\Column(type: 'float')]
    private float $pointsDuel = 0.0;

    #[Assert\NotNull]
    #[ORM\Column(type: 'float')]
    private float $pointsDuelSpecial = 0.0;

    #[Assert\NotNull]
    #[ORM\Column(type: 'float')]
    private float $pointsMulti = 0.0;

    #[Assert\NotNull]
    #[ORM\Column(type: 'float')]
    private float $pointsMultiSpecial = 0.0;

    #[Assert\Uuid]
    #[ORM\Column(type: 'guid', nullable: true)]
    private ?string $scryfallId = null;

    #[Assert\NotNull]
    #[ORM\Column(length: 255)]
    private string $scryfallURI = '';

    public function __construct()
    {
        $this->__traitConstruct();
    }

    public function getManaValue(): float
    {
        return $this->manaValue;
    }

    #[Override]
    public function getMultiCZType(): string
    {
        return $this->multiCZType;
    }

    #[Override]
    public function getNameEN(): string
    {
        return $this->nameEN;
    }

    public function getOracleId(): ?string
    {
        return $this->oracleId;
    }

    #[Override]
    public function getPoints2HG(): float
    {
        return $this->points2HG;
    }

    #[Override]
    public function getPoints2HGSpecial(): float
    {
        return $this->points2HGSpecial;
    }

    #[Override]
    public function getPointsDuel(): float
    {
        return $this->pointsDuel;
    }

    #[Override]
    public function getPointsDuelSpecial(): float
    {
        return $this->pointsDuelSpecial;
    }

    #[Override]
    public function getPointsMulti(): float
    {
        return $this->pointsMulti;
    }

    #[Override]
    public function getPointsMultiSpecial(): float
    {
        return $this->pointsMultiSpecial;
    }

    public function getScryfallId(): ?string
    {
        return $this->scryfallId;
    }

    public function getScryfallURI(): string
    {
        return $this->scryfallURI;
    }

    #[Override]
    public function isLegal2HG(): bool
    {
        return $this->isLegal2HG;
    }

    #[Override]
    public function isLegal2HGSpecial(): bool
    {
        return $this->isLegal2HGSpecial;
    }

    #[Override]
    public function isLegalDuel(): bool
    {
        return $this->isLegalDuel;
    }

    #[Override]
    public function isLegalDuelSpecial(): bool
    {
        return $this->isLegalDuelSpecial;
    }

    #[Override]
    public function isLegalMulti(): bool
    {
        return $this->isLegalMulti;
    }

    #[Override]
    public function isLegalMultiSpecial(): bool
    {
        return $this->isLegalMultiSpecial;
    }

    #[Override]
    public function setIsLegal2HG(bool $isLegal2HG): static
    {
        $this->isLegal2HG = $isLegal2HG;

        return $this;
    }

    #[Override]
    public function setIsLegal2HGSpecial(bool $isLegal2HGSpecial): static
    {
        $this->isLegal2HGSpecial = $isLegal2HGSpecial;

        return $this;
    }

    #[Override]
    public function setIsLegalDuel(bool $isLegalDuel): static
    {
        $this->isLegalDuel = $isLegalDuel;

        return $this;
    }

    #[Override]
    public function setIsLegalDuelSpecial(bool $isLegalDuelSpecial): static
    {
        $this->isLegalDuelSpecial = $isLegalDuelSpecial;

        return $this;
    }

    #[Override]
    public function setIsLegalMulti(bool $isLegalMulti): static
    {
        $this->isLegalMulti = $isLegalMulti;

        return $this;
    }

    #[Override]
    public function setIsLegalMultiSpecial(bool $isLegalMultiSpecial): static
    {
        $this->isLegalMultiSpecial = $isLegalMultiSpecial;

        return $this;
    }

    public function setManaValue(float $manaValue): static
    {
        $this->manaValue = $manaValue;

        return $this;
    }

    #[Override]
    public function setMultiCZType(string $multiCZType): static
    {
        $this->multiCZType = $multiCZType;

        return $this;
    }

    #[Override]
    public function setNameEN(string $nameEN): static
    {
        $this->nameEN = $nameEN;

        return $this;
    }

    public function setOracleId(?string $oracleId): static
    {
        $this->oracleId = $oracleId;

        return $this;
    }

    #[Override]
    public function setPoints2HG(float $points2HG): static
    {
        $this->points2HG = $points2HG;

        return $this;
    }

    #[Override]
    public function setPoints2HGSpecial(float $points2HGSpecial): static
    {
        $this->points2HGSpecial = $points2HGSpecial;

        return $this;
    }

    #[Override]
    public function setPointsDuel(float $pointsDuel): static
    {
        $this->pointsDuel = $pointsDuel;

        return $this;
    }

    #[Override]
    public function setPointsDuelSpecial(float $pointsDuelSpecial): static
    {
        $this->pointsDuelSpecial = $pointsDuelSpecial;

        return $this;
    }

    #[Override]
    public function setPointsMulti(float $pointsMulti): static
    {
        $this->pointsMulti = $pointsMulti;

        return $this;
    }

    #[Override]
    public function setPointsMultiSpecial(float $pointsMultiSpecial): static
    {
        $this->pointsMultiSpecial = $pointsMultiSpecial;

        return $this;
    }

    public function setScryfallId(?string $scryfallId): static
    {
        $this->scryfallId = $scryfallId;

        return $this;
    }

    public function setScryfallURI(string $scryfallURI): static
    {
        $this->scryfallURI = $scryfallURI;

        return $this;
    }
}
