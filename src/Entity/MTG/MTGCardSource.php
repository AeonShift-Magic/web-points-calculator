<?php

declare(strict_types = 1);

namespace App\Entity\MTG;

use App\Entity\HistoryTrackableEntityTrait;
use App\Repository\MTG\MTGCardSourceRepository;
use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: MTGCardSourceRepository::class)]
#[ORM\HasLifecycleCallbacks]
class MTGCardSource
{
    use HistoryTrackableEntityTrait {
        HistoryTrackableEntityTrait::__construct as private __traitConstruct;
    }

    #[ORM\Column]
    #[ORM\GeneratedValue]
    #[ORM\Id]
    private ?int $id = null;

    #[Assert\Length(max: 50)]
    #[Assert\NotBlank]
    #[ORM\Column(length: 50)]
    private string $lastUpdatedFrom = '';

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?DateTime $lastUpdatedAt = null;

    #[Assert\Length(max: 255)]
    #[Assert\NotBlank]
    #[ORM\Column(length: 255)]
    private string $sourceModel = '';

    #[Assert\Length(max: 255)]
    #[Assert\NotBlank]
    #[ORM\Column(length: 255)]
    private string $url = '';

    public function __construct()
    {
        $this->__traitConstruct();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLastUpdatedAt(): ?DateTime
    {
        return $this->lastUpdatedAt;
    }

    public function getLastUpdatedFrom(): string
    {
        return $this->lastUpdatedFrom;
    }

    public function getSourceModel(): string
    {
        return $this->sourceModel;
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function setLastUpdatedAt(?DateTime $lastUpdatedAt): static
    {
        $this->lastUpdatedAt = $lastUpdatedAt;

        return $this;
    }

    public function setLastUpdatedFrom(string $lastUpdatedFrom): static
    {
        $this->lastUpdatedFrom = $lastUpdatedFrom;

        return $this;
    }

    public function setSourceModel(string $sourceModel): static
    {
        $this->sourceModel = $sourceModel;

        return $this;
    }

    public function setUrl(string $url): static
    {
        $this->url = $url;

        return $this;
    }
}
