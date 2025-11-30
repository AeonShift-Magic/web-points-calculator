<?php

declare(strict_types = 1);

namespace App\Entity\MTG;

use App\Repository\MTG\MTGSourceCardRepository;
use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: MTGSourceCardRepository::class)]
#[ORM\HasLifecycleCallbacks]
#[ORM\Index(name: 'idx_oracle_id', columns: ['oracle_id'])]
class MTGSourceCard extends MTGAbstractCard
{
    /**
     * @var list<string>
     */
    #[Assert\NotNull]
    #[ORM\Column(type: Types::JSON)]
    private array $colorIdentity = [];

    #[Assert\NotNull]
    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    private DateTimeImmutable $firstPrintedAt;

    #[Assert\NotNull]
    #[ORM\Column(length: 25)]
    private string $firstPrintedSetCode = '';

    #[Assert\NotNull]
    #[ORM\Column(type: Types::INTEGER)]
    private int $firstPrintedYear = 0;

    #[Assert\NotNull]
    #[ORM\Column]
    private bool $isBlack = false;

    #[Assert\NotNull]
    #[ORM\Column]
    private bool $isBlue = false;

    #[Assert\NotNull]
    #[ORM\Column]
    private bool $isColorless = false;

    #[Assert\NotNull]
    #[ORM\Column]
    private bool $isCommandZoneEligible = false;

    #[Assert\NotNull]
    #[ORM\Column]
    private bool $isGreen = false;

    #[Assert\NotNull]
    #[ORM\Column]
    private bool $isMultipleCommandZoneEligible = false;

    #[Assert\NotNull]
    #[ORM\Column]
    private bool $isRed = false;

    #[Assert\NotNull]
    #[ORM\Column]
    private bool $isWhite = false;

    public function __construct()
    {
        parent::__construct();
        $this->firstPrintedAt = new DateTimeImmutable();
    }

    /**
     * @return list<string>
     */
    public function getColorIdentity(): array
    {
        return $this->colorIdentity;
    }

    public function getFirstPrintedAt(): DateTimeImmutable
    {
        return $this->firstPrintedAt;
    }

    public function getFirstPrintedSetCode(): string
    {
        return $this->firstPrintedSetCode;
    }

    public function getFirstPrintedYear(): int
    {
        return $this->firstPrintedYear;
    }

    public function isBlack(): bool
    {
        return $this->isBlack;
    }

    public function isBlue(): bool
    {
        return $this->isBlue;
    }

    public function isColorless(): bool
    {
        return $this->isColorless;
    }

    public function isCommandZoneEligible(): bool
    {
        return $this->isCommandZoneEligible;
    }

    public function isGreen(): bool
    {
        return $this->isGreen;
    }

    public function isMultipleCommandZoneEligible(): bool
    {
        return $this->isMultipleCommandZoneEligible;
    }

    public function isRed(): bool
    {
        return $this->isRed;
    }

    public function isWhite(): bool
    {
        return $this->isWhite;
    }

    /**
     * @param list<string> $colorIdentity
     */
    public function setColorIdentity(array $colorIdentity): static
    {
        $this->colorIdentity = $colorIdentity;

        return $this;
    }

    public function setFirstPrintedAt(DateTimeImmutable $firstPrintedAt): static
    {
        $this->firstPrintedAt = $firstPrintedAt;

        return $this;
    }

    public function setFirstPrintedSetCode(string $firstPrintedSetCode): self
    {
        $this->firstPrintedSetCode = $firstPrintedSetCode;

        return $this;
    }

    public function setFirstPrintedYear(int $firstPrintedYear): static
    {
        $this->firstPrintedYear = $firstPrintedYear;

        return $this;
    }

    public function setIsBlack(bool $isBlack): static
    {
        $this->isBlack = $isBlack;

        return $this;
    }

    public function setIsBlue(bool $isBlue): static
    {
        $this->isBlue = $isBlue;

        return $this;
    }

    public function setIsColorless(bool $isColorless): static
    {
        $this->isColorless = $isColorless;

        return $this;
    }

    public function setIsCommandZoneEligible(bool $isCommandZoneEligible): static
    {
        $this->isCommandZoneEligible = $isCommandZoneEligible;

        return $this;
    }

    public function setIsGreen(bool $isGreen): static
    {
        $this->isGreen = $isGreen;

        return $this;
    }

    public function setIsMultipleCommandZoneEligible(bool $isMultipleCommandZoneEligible): static
    {
        $this->isMultipleCommandZoneEligible = $isMultipleCommandZoneEligible;

        return $this;
    }

    public function setIsRed(bool $isRed): static
    {
        $this->isRed = $isRed;

        return $this;
    }

    public function setIsWhite(bool $isWhite): static
    {
        $this->isWhite = $isWhite;

        return $this;
    }
}
