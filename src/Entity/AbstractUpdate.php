<?php

declare(strict_types = 1);

namespace App\Entity;

use DateTime;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Override;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\HasLifecycleCallbacks]
#[ORM\MappedSuperclass]
class AbstractUpdate implements UpdateInterface
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
    #[ORM\Column(type: Types::TEXT)]
    private string $descriptionEN = '';

    #[Assert\NotNull]
    #[ORM\Column(type: 'datetime')]
    private DateTime $endingAt;

    #[Assert\NotNull]
    #[ORM\Column]
    private bool $isPublic = false;

    #[Assert\NotNull]
    #[ORM\Column(type: 'datetime')]
    private DateTime $startingAt;

    #[Assert\Length(max: 255)]
    #[Assert\NotNull]
    #[ORM\Column(length: 255)]
    private string $titleEN = '';

    public function __construct()
    {
        $this->__traitConstruct();
        $this->startingAt = new DateTime();
        $this->endingAt = new DateTime();
    }

    #[Override]
    public function getDescriptionEN(): string
    {
        return $this->descriptionEN;
    }

    #[Override]
    public function getEndingAt(): DateTime
    {
        return $this->endingAt;
    }

    #[Override]
    public function getStartingAt(): DateTime
    {
        return $this->startingAt;
    }

    public function getTitleEN(): string
    {
        return $this->titleEN;
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

    #[Override]
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

    #[Override]
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
}
