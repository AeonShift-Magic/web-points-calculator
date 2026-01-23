<?php

declare(strict_types = 1);

namespace App\Entity\MTG;

use App\Entity\HistoryTrackableEntityTrait;
use App\Entity\ItemContractInterface;
use App\Entity\PointsListInterface;
use App\Repository\MTG\MTGPointsListRepository;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Override;
use RuntimeException;
use Stringable;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: MTGPointsListRepository::class)]
#[ORM\HasLifecycleCallbacks]
class MTGPointsList implements PointsListInterface, Stringable
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

    /**
     * @var Collection<int, MTGPointsListCard>
     */
    #[ORM\OneToMany(
        targetEntity: MTGPointsListCard::class,
        mappedBy: 'pointsList',
        cascade: ['persist', 'remove'],
        orphanRemoval: true
    )]
    private Collection $MTGPointListCards;

    /**
     * @var Collection<int, MTGUpdate>
     */
    #[ORM\OneToMany(
        targetEntity: MTGUpdate::class,
        mappedBy: 'pointsList',
    )]
    private Collection $MTGUpdates;

    #[Assert\Length(max: 255)]
    #[Assert\NotNull]
    #[ORM\Column(length: 255)]
    private string $filename = '';

    #[Assert\NotNull]
    #[ORM\Column(type: 'datetime')]
    private DateTime $lastUploadedAt;

    #[Assert\NotNull]
    #[ORM\Column]
    private int $numberOfUploadedCards = 0;

    #[Assert\Type(type: 'numeric')]
    #[ORM\Column(type: 'float', nullable: true)]
    private ?float $pValueBaseQuadruplesLitePlay = 30.0;

    #[Assert\Type(type: 'numeric')]
    #[ORM\Column(type: 'float', nullable: true)]
    private ?float $pValueBaseQuadruplesPowerPlay = 200.0;

    #[Assert\Type(type: 'numeric')]
    #[ORM\Column(type: 'float', nullable: true)]
    private ?float $pValueBaseQuadruplesStandardPlay = 100.0;

    #[Assert\Type(type: 'numeric')]
    #[ORM\Column(type: 'float', nullable: true)]
    private ?float $pValueBaseSingletonLitePlay = 30.0;

    #[Assert\Type(type: 'numeric')]
    #[ORM\Column(type: 'float', nullable: true)]
    private ?float $pValueBaseSingletonPowerPlay = 200.0;

    #[Assert\Type(type: 'numeric')]
    #[ORM\Column(type: 'float', nullable: true)]
    private ?float $pValueBaseSingletonStandardPlay = 100.0;

    #[Assert\Type(type: 'numeric')]
    #[ORM\Column(type: 'float', nullable: true)]
    private ?float $pValueCommanderLitePlay = 30.0;

    #[Assert\Type(type: 'numeric')]
    #[ORM\Column(type: 'float', nullable: true)]
    private ?float $pValueCommanderPowerPlay = 200.0;

    #[Assert\Type(type: 'numeric')]
    #[ORM\Column(type: 'float', nullable: true)]
    private ?float $pValueCommanderStandardPlay = 100.0;

    #[Assert\Type(type: 'numeric')]
    #[ORM\Column(type: 'float', nullable: true)]
    private ?float $pValueDuelCommanderLitePlay = 30.0;

    #[Assert\Type(type: 'numeric')]
    #[ORM\Column(type: 'float', nullable: true)]
    private ?float $pValueDuelCommanderPowerPlay = 200.0;

    #[Assert\Type(type: 'numeric')]
    #[ORM\Column(type: 'float', nullable: true)]
    private ?float $pValueDuelCommanderStandardPlay = 100.0;

    #[Assert\Type(type: 'numeric')]
    #[ORM\Column(type: 'float', nullable: true)]
    private ?float $pValueHighlanderLitePlay = 30.0;

    #[Assert\Type(type: 'numeric')]
    #[ORM\Column(type: 'float', nullable: true)]
    private ?float $pValueHighlanderPowerPlay = 200.0;

    #[Assert\Type(type: 'numeric')]
    #[ORM\Column(type: 'float', nullable: true)]
    private ?float $pValueHighlanderStandardPlay = 100.0;

    #[Assert\Type(type: 'numeric')]
    #[ORM\Column(type: 'float', nullable: true)]
    private ?float $pValueModernLitePlay = 30.0;

    #[Assert\Type(type: 'numeric')]
    #[ORM\Column(type: 'float', nullable: true)]
    private ?float $pValueModernPowerPlay = 200.0;

    #[Assert\Type(type: 'numeric')]
    #[ORM\Column(type: 'float', nullable: true)]
    private ?float $pValueModernStandardPlay = 100.0;

    #[Assert\Type(type: 'numeric')]
    #[ORM\Column(type: 'float', nullable: true)]
    private ?float $pValuePioneerLitePlay = 30.0;

    #[Assert\Type(type: 'numeric')]
    #[ORM\Column(type: 'float', nullable: true)]
    private ?float $pValuePioneerPowerPlay = 200.0;

    #[Assert\Type(type: 'numeric')]
    #[ORM\Column(type: 'float', nullable: true)]
    private ?float $pValuePioneerStandardPlay = 100.0;

    #[Assert\Type(type: 'numeric')]
    #[ORM\Column(type: 'float', nullable: true)]
    private ?float $pValueStandardLitePlay = 30.0;

    #[Assert\Type(type: 'numeric')]
    #[ORM\Column(type: 'float', nullable: true)]
    private ?float $pValueStandardPowerPlay = 200.0;

    #[Assert\Type(type: 'numeric')]
    #[ORM\Column(type: 'float', nullable: true)]
    private ?float $pValueStandardStandardPlay = 100.0;

    #[Assert\Length(max: 255)]
    #[Assert\NotNull]
    #[ORM\Column(length: 255)]
    private string $rulesModel = '';

    #[Assert\Length(max: 255)]
    #[Assert\NotNull]
    #[ORM\Column(length: 255)]
    private string $rulesModelName = '';

    #[Assert\Length(max: 255)]
    #[Assert\NotNull]
    #[ORM\Column(length: 255)]
    private string $title = '';

    #[Assert\NotNull]
    #[ORM\Column(type: 'datetime')]
    private DateTime $validityStartingAt;

    public function __construct()
    {
        $this->__traitConstruct();
        $this->lastUploadedAt = new DateTime();
        $this->validityStartingAt = new DateTime();
        $this->MTGPointListCards = new ArrayCollection();
        $this->MTGUpdates = new ArrayCollection();
    }

    #[Override]
    public function __toString()
    {
        return $this->getTitle() . ' [' . $this->getValidityStartingAt()->format('Y-m-d H:i') . ']' . ' [' . $this->MTGPointListCards->count() . ' cards]';
    }

    public function getFilename(): string
    {
        return $this->filename;
    }

    /**
     * @return array<ItemContractInterface>
     */
    #[Override]
    public function getItems(): array
    {
        return $this->MTGPointListCards->toArray();
    }

    public function getLastUploadedAt(): DateTime
    {
        return $this->lastUploadedAt;
    }

    public function getMTGPointListCards(): Collection
    {
        return $this->MTGPointListCards;
    }

    public function getMTGUpdates(): Collection
    {
        return $this->MTGUpdates;
    }

    public function getNumberOfUploadedCards(): int
    {
        return $this->numberOfUploadedCards;
    }

    public function getPValueBaseQuadruplesLitePlay(): ?float
    {
        return $this->pValueBaseQuadruplesLitePlay;
    }

    public function getPValueBaseQuadruplesPowerPlay(): ?float
    {
        return $this->pValueBaseQuadruplesPowerPlay;
    }

    public function getPValueBaseQuadruplesStandardPlay(): ?float
    {
        return $this->pValueBaseQuadruplesStandardPlay;
    }

    public function getPValueBaseSingletonLitePlay(): ?float
    {
        return $this->pValueBaseSingletonLitePlay;
    }

    public function getPValueBaseSingletonPowerPlay(): ?float
    {
        return $this->pValueBaseSingletonPowerPlay;
    }

    public function getPValueBaseSingletonStandardPlay(): ?float
    {
        return $this->pValueBaseSingletonStandardPlay;
    }

    public function getPValueCommanderLitePlay(): ?float
    {
        return $this->pValueCommanderLitePlay;
    }

    public function getPValueCommanderPowerPlay(): ?float
    {
        return $this->pValueCommanderPowerPlay;
    }

    public function getPValueCommanderStandardPlay(): ?float
    {
        return $this->pValueCommanderStandardPlay;
    }

    public function getPValueDuelCommanderLitePlay(): ?float
    {
        return $this->pValueDuelCommanderLitePlay;
    }

    public function getPValueDuelCommanderPowerPlay(): ?float
    {
        return $this->pValueDuelCommanderPowerPlay;
    }

    public function getPValueDuelCommanderStandardPlay(): ?float
    {
        return $this->pValueDuelCommanderStandardPlay;
    }

    public function getPValueHighlanderLitePlay(): ?float
    {
        return $this->pValueHighlanderLitePlay;
    }

    public function getPValueHighlanderPowerPlay(): ?float
    {
        return $this->pValueHighlanderPowerPlay;
    }

    public function getPValueHighlanderStandardPlay(): ?float
    {
        return $this->pValueHighlanderStandardPlay;
    }

    public function getPValueModernLitePlay(): ?float
    {
        return $this->pValueModernLitePlay;
    }

    public function getPValueModernPowerPlay(): ?float
    {
        return $this->pValueModernPowerPlay;
    }

    public function getPValueModernStandardPlay(): ?float
    {
        return $this->pValueModernStandardPlay;
    }

    public function getPValuePioneerLitePlay(): ?float
    {
        return $this->pValuePioneerLitePlay;
    }

    public function getPValuePioneerPowerPlay(): ?float
    {
        return $this->pValuePioneerPowerPlay;
    }

    public function getPValuePioneerStandardPlay(): ?float
    {
        return $this->pValuePioneerStandardPlay;
    }

    public function getPValueStandardLitePlay(): ?float
    {
        return $this->pValueStandardLitePlay;
    }

    public function getPValueStandardPowerPlay(): ?float
    {
        return $this->pValueStandardPowerPlay;
    }

    public function getPValueStandardStandardPlay(): ?float
    {
        return $this->pValueStandardStandardPlay;
    }

    #[Override]
    public function getRulesModel(): string
    {
        return $this->rulesModel;
    }

    public function getRulesModelName(): string
    {
        return $this->rulesModelName;
    }

    #[Override]
    public function getTitle(): string
    {
        return $this->title;
    }

    #[Override]
    public function getValidityStartingAt(): DateTime
    {
        return $this->validityStartingAt;
    }

    #[ORM\PreRemove]
    public function preventRemovalIfUpdatesExist(): void
    {
        if (! $this->MTGUpdates->isEmpty()) {
            throw new RuntimeException(
                'Cannot delete a points list that is referenced by one or more updates.'
            );
        }
    }

    public function setFilename(string $filename): static
    {
        $this->filename = $filename;

        return $this;
    }

    public function setLastUploadedAt(DateTime $lastUploadedAt): static
    {
        $this->lastUploadedAt = $lastUploadedAt;

        return $this;
    }

    public function setMTGPointListCards(Collection $MTGPointListCards): self
    {
        $this->MTGPointListCards = $MTGPointListCards;

        return $this;
    }

    public function setMTGUpdates(Collection $MTGUpdates): self
    {
        $this->MTGUpdates = $MTGUpdates;

        return $this;
    }

    public function setNumberOfUploadedCards(int $numberOfUploadedCards): static
    {
        $this->numberOfUploadedCards = $numberOfUploadedCards;

        return $this;
    }

    public function setPValueBaseQuadruplesLitePlay(?float $pValueBaseQuadruplesLitePlay): self
    {
        $this->pValueBaseQuadruplesLitePlay = $pValueBaseQuadruplesLitePlay;

        return $this;
    }

    public function setPValueBaseQuadruplesPowerPlay(?float $pValueBaseQuadruplesPowerPlay): self
    {
        $this->pValueBaseQuadruplesPowerPlay = $pValueBaseQuadruplesPowerPlay;

        return $this;
    }

    public function setPValueBaseQuadruplesStandardPlay(?float $pValueBaseQuadruplesStandardPlay): self
    {
        $this->pValueBaseQuadruplesStandardPlay = $pValueBaseQuadruplesStandardPlay;

        return $this;
    }

    public function setPValueBaseSingletonLitePlay(?float $pValueBaseSingletonLitePlay): self
    {
        $this->pValueBaseSingletonLitePlay = $pValueBaseSingletonLitePlay;

        return $this;
    }

    public function setPValueBaseSingletonPowerPlay(?float $pValueBaseSingletonPowerPlay): self
    {
        $this->pValueBaseSingletonPowerPlay = $pValueBaseSingletonPowerPlay;

        return $this;
    }

    public function setPValueBaseSingletonStandardPlay(?float $pValueBaseSingletonStandardPlay): self
    {
        $this->pValueBaseSingletonStandardPlay = $pValueBaseSingletonStandardPlay;

        return $this;
    }

    public function setPValueCommanderLitePlay(?float $pValueCommanderLitePlay): self
    {
        $this->pValueCommanderLitePlay = $pValueCommanderLitePlay;

        return $this;
    }

    public function setPValueCommanderPowerPlay(?float $pValueCommanderPowerPlay): self
    {
        $this->pValueCommanderPowerPlay = $pValueCommanderPowerPlay;

        return $this;
    }

    public function setPValueCommanderStandardPlay(?float $pValueCommanderStandardPlay): self
    {
        $this->pValueCommanderStandardPlay = $pValueCommanderStandardPlay;

        return $this;
    }

    public function setPValueDuelCommanderLitePlay(?float $pValueDuelCommanderLitePlay): self
    {
        $this->pValueDuelCommanderLitePlay = $pValueDuelCommanderLitePlay;

        return $this;
    }

    public function setPValueDuelCommanderPowerPlay(?float $pValueDuelCommanderPowerPlay): self
    {
        $this->pValueDuelCommanderPowerPlay = $pValueDuelCommanderPowerPlay;

        return $this;
    }

    public function setPValueDuelCommanderStandardPlay(?float $pValueDuelCommanderStandardPlay): self
    {
        $this->pValueDuelCommanderStandardPlay = $pValueDuelCommanderStandardPlay;

        return $this;
    }

    public function setPValueHighlanderLitePlay(?float $pValueHighlanderLitePlay): self
    {
        $this->pValueHighlanderLitePlay = $pValueHighlanderLitePlay;

        return $this;
    }

    public function setPValueHighlanderPowerPlay(?float $pValueHighlanderPowerPlay): self
    {
        $this->pValueHighlanderPowerPlay = $pValueHighlanderPowerPlay;

        return $this;
    }

    public function setPValueHighlanderStandardPlay(?float $pValueHighlanderStandardPlay): self
    {
        $this->pValueHighlanderStandardPlay = $pValueHighlanderStandardPlay;

        return $this;
    }

    public function setPValueModernLitePlay(?float $pValueModernLitePlay): self
    {
        $this->pValueModernLitePlay = $pValueModernLitePlay;

        return $this;
    }

    public function setPValueModernPowerPlay(?float $pValueModernPowerPlay): self
    {
        $this->pValueModernPowerPlay = $pValueModernPowerPlay;

        return $this;
    }

    public function setPValueModernStandardPlay(?float $pValueModernStandardPlay): self
    {
        $this->pValueModernStandardPlay = $pValueModernStandardPlay;

        return $this;
    }

    public function setPValuePioneerLitePlay(?float $pValuePioneerLitePlay): self
    {
        $this->pValuePioneerLitePlay = $pValuePioneerLitePlay;

        return $this;
    }

    public function setPValuePioneerPowerPlay(?float $pValuePioneerPowerPlay): self
    {
        $this->pValuePioneerPowerPlay = $pValuePioneerPowerPlay;

        return $this;
    }

    public function setPValuePioneerStandardPlay(?float $pValuePioneerStandardPlay): self
    {
        $this->pValuePioneerStandardPlay = $pValuePioneerStandardPlay;

        return $this;
    }

    public function setPValueStandardLitePlay(?float $pValueStandardLitePlay): self
    {
        $this->pValueStandardLitePlay = $pValueStandardLitePlay;

        return $this;
    }

    public function setPValueStandardPowerPlay(?float $pValueStandardPowerPlay): self
    {
        $this->pValueStandardPowerPlay = $pValueStandardPowerPlay;

        return $this;
    }

    public function setPValueStandardStandardPlay(?float $pValueStandardStandardPlay): self
    {
        $this->pValueStandardStandardPlay = $pValueStandardStandardPlay;

        return $this;
    }

    #[Override]
    public function setRulesModel(string $rulesModel): static
    {
        $this->rulesModel = $rulesModel;

        return $this;
    }

    public function setRulesModelName(string $rulesModelName): self
    {
        $this->rulesModelName = $rulesModelName;

        return $this;
    }

    #[Override]
    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    #[Override]
    public function setValidityStartingAt(DateTime $validityStartingAt): static
    {
        $this->validityStartingAt = $validityStartingAt;

        return $this;
    }
}
