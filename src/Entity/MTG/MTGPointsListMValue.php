<?php

declare(strict_types = 1);

namespace App\Entity\MTG;

use App\Entity\HistoryTrackableEntityTrait;
use App\Repository\MTG\MTGPointsListMValueRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: MTGPointsListMValueRepository::class)]
#[ORM\Index(name: 'idx_name_en', columns: ['name_en'])]
class MTGPointsListMValue
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
    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private string $MTGOPrice = '0.00';

    /**
     * @var numeric-string $MValueTrend the average value of the card in the market, in mixed EUR/USD
     */
    #[Assert\NotNull]
    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private string $MValueTrend = '0.00';

    #[Assert\Length(max: 255)]
    #[Assert\NotNull]
    #[ORM\Column(name: 'name_en', length: 255)]
    private string $nameEN = '';

    #[Assert\NotNull]
    #[ORM\JoinColumn(nullable: true)]
    #[ORM\ManyToOne(targetEntity: MTGPointsList::class, inversedBy: 'MTGPointListMValues')]
    private ?MTGPointsList $pointsList = null;

    #[Assert\Type(type: 'numeric')]
    #[ORM\Column(name: 'value_points', type: 'float', nullable: true)]
    private ?float $valuePoints = null;

    public function __construct()
    {
        $this->__traitConstruct();
    }

    public function getMTGOPrice(): string
    {
        return $this->MTGOPrice;
    }

    public function getMValueTrend(): string
    {
        return $this->MValueTrend;
    }

    public function getNameEN(): string
    {
        return $this->nameEN;
    }

    public function getPointsList(): ?MTGPointsList
    {
        return $this->pointsList;
    }

    public function getValuePoints(): ?float
    {
        return $this->valuePoints;
    }

    public function setMTGOPrice(string $MTGOPrice): self
    {
        $this->MTGOPrice = $MTGOPrice;

        return $this;
    }

    /**
     * @param numeric-string $MValueTrend
     *
     * @return $this
     */
    public function setMValueTrend(string $MValueTrend): self
    {
        $this->MValueTrend = $MValueTrend;

        return $this;
    }

    public function setNameEN(string $nameEN): self
    {
        $this->nameEN = $nameEN;

        return $this;
    }

    public function setPointsList(MTGPointsList|null $list): self
    {
        $this->pointsList = $list;

        return $this;
    }

    public function setValuePoints(?float $valuePoints): self
    {
        $this->valuePoints = $valuePoints;

        return $this;
    }
}
