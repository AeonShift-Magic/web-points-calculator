<?php

declare(strict_types = 1);

namespace App\Entity\MTG;

use App\Entity\HistoryTrackableEntityTrait;
use App\Entity\UpdateInterface;
use App\Entity\User;
use App\Repository\MTG\MTGUpdateRepository;
use DateTime;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: MTGUpdateRepository::class)]
#[ORM\HasLifecycleCallbacks]
class MTGUpdate implements UpdateInterface
{
    use HistoryTrackableEntityTrait {
        HistoryTrackableEntityTrait::__construct as private __traitConstruct;
    }

    #[Assert\NotNull]
    #[ORM\JoinColumn(nullable: false)]
    #[ORM\ManyToOne(targetEntity: MTGCardSource::class)]
    private ?MTGCardSource $MTGCardSource = null;

    #[Assert\NotNull]
    #[ORM\Column(type: Types::TEXT)]
    private string $descriptionEN = '';

    #[Assert\NotNull]
    #[ORM\Column(type: 'datetime')]
    private DateTime $endingAt;

    #[ORM\Column]
    #[ORM\GeneratedValue]
    #[ORM\Id]
    private ?int $id = null;

    #[Assert\NotNull]
    #[ORM\Column]
    private bool $isPublic = false;

    #[Assert\NotNull]
    #[ORM\JoinColumn(nullable: false)]
    #[ORM\ManyToOne(targetEntity: MTGPointsList::class)]
    private ?MTGPointsList $pointsList = null;

    #[Assert\Length(max: 255)]
    #[Assert\NotNull]
    #[ORM\Column(length: 255)]
    private string $rulesModel = '';

    #[Assert\NotNull]
    #[ORM\Column(type: 'datetime')]
    private DateTime $startingAt;

    #[Assert\Length(max: 255)]
    #[Assert\NotNull]
    #[ORM\Column(length: 255)]
    private string $titleEN = '';

    #[Assert\NotNull]
    #[ORM\JoinColumn(nullable: false)]
    #[ORM\ManyToOne(targetEntity: User::class)]
    private ?User $user = null;

    public function __construct()
    {
        $this->__traitConstruct();
        $this->startingAt = new DateTime();
        $this->endingAt = new DateTime();
    }

    public function getDescriptionEN(): string
    {
        return $this->descriptionEN;
    }

    public function getEndingAt(): DateTime
    {
        return $this->endingAt;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getMTGCardSource(): ?MTGCardSource
    {
        return $this->MTGCardSource;
    }

    public function getPointsList(): ?MTGPointsList
    {
        return $this->pointsList;
    }

    public function getRulesModel(): string
    {
        return $this->rulesModel;
    }

    public function getStartingAt(): DateTime
    {
        return $this->startingAt;
    }

    public function getTitleEN(): string
    {
        return $this->titleEN;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function isPublic(): bool
    {
        return $this->isPublic;
    }

    public function setDescriptionEN(string $descriptionEN): static
    {
        $this->descriptionEN = $descriptionEN;

        return $this;
    }

    public function setEndingAt(DateTime $endingAt): static
    {
        $this->endingAt = $endingAt;

        return $this;
    }

    public function setIsPublic(bool $isPublic): static
    {
        $this->isPublic = $isPublic;

        return $this;
    }

    public function setMTGCardSource(?MTGCardSource $MTGCardSource): static
    {
        $this->MTGCardSource = $MTGCardSource;

        return $this;
    }

    public function setPointsList(?MTGPointsList $pointsList): static
    {
        $this->pointsList = $pointsList;

        return $this;
    }

    public function setRulesModel(string $rulesModel): static
    {
        $this->rulesModel = $rulesModel;

        return $this;
    }

    public function setStartingAt(DateTime $startingAt): static
    {
        $this->startingAt = $startingAt;

        return $this;
    }

    public function setTitleEN(string $titleEN): static
    {
        $this->titleEN = $titleEN;

        return $this;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }
}
