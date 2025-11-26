<?php

declare(strict_types = 1);

namespace App\Entity\MTG;

use App\Entity\HistoryTrackableEntityTrait;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\HasLifecycleCallbacks]
#[ORM\MappedSuperclass]
abstract class AbstractMTGCard implements MTGCardInterface
{
    use HistoryTrackableEntityTrait {
        HistoryTrackableEntityTrait::__construct as private __traitConstruct;
    }

    #[ORM\Column]
    #[ORM\GeneratedValue]
    #[ORM\Id]
    private ?int $id = null;

    #[Assert\Length(max: 255)]
    #[Assert\NotNull]
    #[ORM\Column(length: 255)]
    private string $nameEN = '';

    #[Assert\Uuid]
    #[ORM\Column(type: 'guid', nullable: true)]
    private ?string $oracleId = null;

    #[Assert\NotNull]
    #[ORM\Column(type: 'float')]
    private float $points2HG = 0.0;

    #[Assert\NotNull]
    #[ORM\Column(type: 'float')]
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

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNameEN(): string
    {
        return $this->nameEN;
    }

    public function getOracleId(): ?string
    {
        return $this->oracleId;
    }

    public function getPoints2HG(): float
    {
        return $this->points2HG;
    }

    public function getPointsDuel(): float
    {
        return $this->pointsDuel;
    }

    public function getPointsDuelSpecial(): float
    {
        return $this->pointsDuelSpecial;
    }

    public function getScryfallId(): ?string
    {
        return $this->scryfallId;
    }

    public function getScryfallURI(): string
    {
        return $this->scryfallURI;
    }

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

    public function setPoints2HG(float $points2HG): static
    {
        $this->points2HG = $points2HG;

        return $this;
    }

    public function setPointsDuel(float $pointsDuel): static
    {
        $this->pointsDuel = $pointsDuel;

        return $this;
    }

    public function setPointsDuelSpecial(float $pointsDuelSpecial): static
    {
        $this->pointsDuelSpecial = $pointsDuelSpecial;

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
