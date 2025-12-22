<?php

declare(strict_types = 1);

namespace App\Entity\MTG;

use App\Entity\HistoryTrackableEntityTrait;
use App\Entity\User;
use App\Repository\MTG\MTGPointsListRepository;
use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: MTGPointsListRepository::class)]
#[ORM\HasLifecycleCallbacks]
class MTGPointsList
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
    private string $filename = '';

    #[Assert\NotNull]
    #[ORM\Column(type: 'datetime')]
    private DateTime $lastUploadedAt;

    #[Assert\NotNull]
    #[ORM\Column]
    private int $nbCards = 0;

    #[Assert\Length(max: 255)]
    #[Assert\NotNull]
    #[ORM\Column(length: 255)]
    private string $title = '';

    #[Assert\NotNull]
    #[ORM\JoinColumn(nullable: true)]
    #[ORM\ManyToOne(targetEntity: User::class)]
    private ?User $user = null;

    public function __construct()
    {
        $this->__traitConstruct();
        $this->lastUploadedAt = new DateTime();
    }

    public function getFilename(): string
    {
        return $this->filename;
    }

    public function getLastUploadedAt(): DateTime
    {
        return $this->lastUploadedAt;
    }

    public function getNbCards(): int
    {
        return $this->nbCards;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getUser(): ?User
    {
        return $this->user;
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

    public function setNbCards(int $nbCards): static
    {
        $this->nbCards = $nbCards;

        return $this;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }
}
