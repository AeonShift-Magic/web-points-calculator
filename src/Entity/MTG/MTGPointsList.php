<?php

declare(strict_types = 1);

namespace App\Entity\MTG;

use App\Entity\HistoryTrackableEntityTrait;
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

    #[Assert\Length(max: 255)]
    #[Assert\NotNull]
    #[ORM\Column(length: 255)]
    private string $rulesModel = '';

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

    #[Override]
    public function getRulesModel(): string
    {
        return $this->rulesModel;
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

    #[Override]
    public function setRulesModel(string $rulesModel): static
    {
        $this->rulesModel = $rulesModel;

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
