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

    #[Assert\Length(max: 255)]
    #[Assert\NotNull]
    #[ORM\Column(length: 255)]
    private string $flavorOfNameEN = '';

    #[Assert\NotNull]
    #[ORM\Column(name: 'is_legal_2hg')]
    private bool $isLegal2HG = false;

    #[Assert\NotNull]
    #[ORM\Column(name: 'is_legal_2hg_special')]
    private bool $isLegal2HGSpecial = false;

    #[Assert\NotNull]
    #[ORM\Column]
    private bool $isLegalCommander = false;

    #[Assert\NotNull]
    #[ORM\Column]
    private bool $isLegalCommanderSpecial = false;

    #[Assert\NotNull]
    #[ORM\Column]
    private bool $isLegalDuelCommander = false;

    #[Assert\NotNull]
    #[ORM\Column]
    private bool $isLegalDuelCommanderSpecial = false;

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

    #[Assert\Type(type: 'numeric')]
    #[ORM\Column(name: 'points_2hg', type: 'float', nullable: true)]
    private ?float $points2HG = null;

    #[Assert\Type(type: 'numeric')]
    #[ORM\Column(name: 'points_2hg_special', type: 'float', nullable: true)]
    private ?float $points2HGSpecial = null;

    #[Assert\Type(type: 'numeric')]
    #[ORM\Column(type: 'float', nullable: true)]
    private ?float $pointsBaseQuadruples = null;

    #[Assert\Type(type: 'numeric')]
    #[ORM\Column(type: 'float', nullable: true)]
    private ?float $pointsBaseSingleton = null;

    #[Assert\Type(type: 'numeric')]
    #[ORM\Column(type: 'float', nullable: true)]
    private ?float $pointsCommander = null;

    #[Assert\Type(type: 'numeric')]
    #[ORM\Column(type: 'float', nullable: true)]
    private ?float $pointsCommanderSpecial = null;

    #[Assert\Type(type: 'numeric')]
    #[ORM\Column(type: 'float', nullable: true)]
    private ?float $pointsDuelCommander = null;

    #[Assert\Type(type: 'numeric')]
    #[ORM\Column(type: 'float', nullable: true)]
    private ?float $pointsDuelCommanderSpecial = null;

    #[Assert\Type(type: 'numeric')]
    #[ORM\Column(type: 'float', nullable: true)]
    private ?float $pointsHighlander = null;

    #[Assert\Type(type: 'numeric')]
    #[ORM\Column(type: 'float', nullable: true)]
    private ?float $pointsModern = null;

    #[Assert\Type(type: 'numeric')]
    #[ORM\Column(type: 'float', nullable: true)]
    private ?float $pointsPioneer = null;

    #[Assert\Type(type: 'numeric')]
    #[ORM\Column(type: 'float', nullable: true)]
    private ?float $pointsStandard = null;

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

    /**
     * @return array <string, float|null> the real points (with precedence) of a card as an associative array
     */
    public function getCalculatorPointsAsArray(): array
    {
        $points = [];

        // Base points first, default to 0.0 if not set.
        $points['basequadruples'] = $this->pointsBaseQuadruples ?? 0.0;
        $points['basesingletonrules'] = $this->pointsBaseSingleton ?? 0.0;

        // Then, format-specific points, in order of precedence.

        // Highlander
        $points['highlander'] = $this->pointsHighlander ?? $points['basesingletonrules'];

        // Duel Commander
        $points['duelcommander'] = $this->pointsDuelCommander ?? $points['basesingletonrules'];
        // Duel Commander Special
        if ($this->pointsDuelCommanderSpecial !== null) {
            $points['duelcommanderspecial'] = $this->pointsDuelCommanderSpecial;
        } else {
            $points['duelcommanderspecial'] = $points['duelcommander'];
        }

        // Commander
        $points['commander'] = $this->pointsCommander ?? $points['basesingletonrules'];
        // Commander Special
        if ($this->pointsCommanderSpecial !== null) {
            $points['commanderspecial'] = $this->pointsCommanderSpecial;
        } else {
            $points['commanderspecial'] = $points['commander'];
        }

        // 2HG
        $points['2hg'] = $this->points2HG ?? $points['basequadruples'];
        // 2HG Special
        if ($this->points2HGSpecial !== null) {
            $points['2hgspecial'] = $this->points2HGSpecial;
        } else {
            $points['2hgspecial'] = $points['2hg'];
        }

        // Modern
        $points['modern'] = $this->pointsModern ?? $points['basequadruples'];

        // Pioneer
        $points['pioneer'] = $this->pointsPioneer ?? $points['basequadruples'];

        // Standard
        $points['standard'] = $this->pointsStandard ?? $points['basequadruples'];

        return $points;
    }

    public function getFlavorOfNameEN(): string
    {
        return $this->flavorOfNameEN;
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

    public function getPoints2HG(): ?float
    {
        return $this->points2HG;
    }

    public function getPoints2HGSpecial(): ?float
    {
        return $this->points2HGSpecial;
    }

    #[Override]
    public function getPointsBaseQuadruples(): ?float
    {
        return $this->pointsBaseQuadruples;
    }

    #[Override]
    public function getPointsBaseSingleton(): ?float
    {
        return $this->pointsBaseSingleton;
    }

    #[Override]
    public function getPointsCommander(): ?float
    {
        return $this->pointsCommander;
    }

    #[Override]
    public function getPointsCommanderSpecial(): ?float
    {
        return $this->pointsCommanderSpecial;
    }

    #[Override]
    public function getPointsDuelCommander(): ?float
    {
        return $this->pointsDuelCommander;
    }

    #[Override]
    public function getPointsDuelCommanderSpecial(): ?float
    {
        return $this->pointsDuelCommanderSpecial;
    }

    public function getPointsHighlander(): ?float
    {
        return $this->pointsHighlander;
    }

    public function getPointsModern(): ?float
    {
        return $this->pointsModern;
    }

    public function getPointsPioneer(): ?float
    {
        return $this->pointsPioneer;
    }

    public function getPointsStandard(): ?float
    {
        return $this->pointsStandard;
    }

    /**
     * @return array <string, float|null> the raw points (no precedence) of a card as an associative array
     */
    public function getRawPointsAsArray(): array
    {
        return [
            'standard'             => $this->pointsStandard,
            'pioneer'              => $this->pointsPioneer,
            'modern'               => $this->pointsModern,
            'highlander'           => $this->pointsHighlander,
            'commander'            => $this->pointsCommander,
            'commanderspecial'     => $this->pointsCommanderSpecial,
            'duelcommander'        => $this->pointsDuelCommander,
            'duelcommanderspecial' => $this->pointsDuelCommanderSpecial,
            '2hg'                  => $this->points2HG,
            '2hgspecial'           => $this->points2HGSpecial,
            'basequadruples'       => $this->pointsBaseQuadruples,
            'basesingletonrules'   => $this->pointsBaseSingleton,
        ];
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
    public function isLegalCommander(): bool
    {
        return $this->isLegalCommander;
    }

    #[Override]
    public function isLegalCommanderSpecial(): bool
    {
        return $this->isLegalCommanderSpecial;
    }

    #[Override]
    public function isLegalDuelCommander(): bool
    {
        return $this->isLegalDuelCommander;
    }

    #[Override]
    public function isLegalDuelCommanderSpecial(): bool
    {
        return $this->isLegalDuelCommanderSpecial;
    }

    public function setFlavorOfNameEN(string $flavorOfNameEN): self
    {
        $this->flavorOfNameEN = $flavorOfNameEN;

        return $this;
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
    public function setIsLegalCommander(bool $isLegalMulti): static
    {
        $this->isLegalCommander = $isLegalMulti;

        return $this;
    }

    #[Override]
    public function setIsLegalCommanderSpecial(bool $isLegalMultiSpecial): static
    {
        $this->isLegalCommanderSpecial = $isLegalMultiSpecial;

        return $this;
    }

    #[Override]
    public function setIsLegalDuelCommander(bool $isLegalDuel): static
    {
        $this->isLegalDuelCommander = $isLegalDuel;

        return $this;
    }

    #[Override]
    public function setIsLegalDuelCommanderSpecial(bool $isLegalDuelSpecial): static
    {
        $this->isLegalDuelCommanderSpecial = $isLegalDuelSpecial;

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

    public function setPoints2HG(?float $points2HG): static
    {
        $this->points2HG = $points2HG;

        return $this;
    }

    public function setPoints2HGSpecial(?float $points2HGSpecial): static
    {
        $this->points2HGSpecial = $points2HGSpecial;

        return $this;
    }

    #[Override]
    public function setPointsBaseQuadruples(?float $pointsBaseQuadruples): static
    {
        $this->pointsBaseQuadruples = $pointsBaseQuadruples;

        return $this;
    }

    #[Override]
    public function setPointsBaseSingleton(?float $pointsBaseSingleton): static
    {
        $this->pointsBaseSingleton = $pointsBaseSingleton;

        return $this;
    }

    #[Override]
    public function setPointsCommander(?float $pointsCommander): static
    {
        $this->pointsCommander = $pointsCommander;

        return $this;
    }

    #[Override]
    public function setPointsCommanderSpecial(?float $pointsCommanderSpecial): static
    {
        $this->pointsCommanderSpecial = $pointsCommanderSpecial;

        return $this;
    }

    #[Override]
    public function setPointsDuelCommander(?float $pointsDuelCommander): static
    {
        $this->pointsDuelCommander = $pointsDuelCommander;

        return $this;
    }

    #[Override]
    public function setPointsDuelCommanderSpecial(?float $pointsDuelCommanderSpecial): static
    {
        $this->pointsDuelCommanderSpecial = $pointsDuelCommanderSpecial;

        return $this;
    }

    public function setPointsHighlander(?float $pointsHighlander): static
    {
        $this->pointsHighlander = $pointsHighlander;

        return $this;
    }

    public function setPointsModern(?float $pointsModern): static
    {
        $this->pointsModern = $pointsModern;

        return $this;
    }

    public function setPointsPioneer(?float $pointsPioneer): static
    {
        $this->pointsPioneer = $pointsPioneer;

        return $this;
    }

    public function setPointsStandard(?float $pointsStandard): static
    {
        $this->pointsStandard = $pointsStandard;

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
