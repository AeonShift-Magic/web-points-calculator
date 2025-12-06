<?php

declare(strict_types = 1);

namespace App\Entity;

use DateTime;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
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
    #[ORM\JoinColumn(nullable: true)]
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
