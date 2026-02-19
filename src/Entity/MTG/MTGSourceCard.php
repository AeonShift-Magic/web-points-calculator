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
#[ORM\Index(name: 'idx_oracle_name', columns: ['name_en'])]
class MTGSourceCard extends MTGAbstractCard
{
    /** The count of prices to retain for the average value calculation. */
    public const int PRICE_RETENTION_COUNT = 30;

    /**
     * @var int $CEDHRank the rank on EDHREC (the lower the better)
     */
    #[Assert\NotNull]
    #[ORM\Column(type: Types::INTEGER)]
    private int $CEDHRank = 0;

    /**
     * @var int $FFARank the rank on EDHREC (the lower the better)
     */
    #[Assert\NotNull]
    #[ORM\Column(type: Types::INTEGER)]
    private int $FFARank = 0;

    #[Assert\NotNull]
    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private string $MTGOPrice = '0.00';

    /**
     * @var int $MValueCount the number of counted prices updates for the average value
     */
    #[Assert\NotNull]
    #[ORM\Column(type: Types::INTEGER)]
    private int $MValueCount = 0;

    /**
     * @var numeric-string $MValueTrend the average value of the card in the market, in mixed EUR/USD
     */
    #[Assert\NotNull]
    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private string $MValueTrend = '0.00';

    /**
     * @var list<string>
     */
    #[Assert\NotNull]
    #[ORM\Column(type: Types::JSON)]
    private array $colorIdentity = [];

    /**
     * @var int $duelRank the rank on MTGTop8 (the lower the better)
     */
    #[Assert\NotNull]
    #[ORM\Column(type: Types::INTEGER)]
    private int $duelRank = 0;

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
    #[ORM\Column(length: 255)]
    private string $imageURL = '';

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
    private bool $isDigitalOnly = false;

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

    /**
     * @var numeric-string $latestMValue the average value of the card in the market, in mixed EUR/USD
     */
    #[Assert\NotNull]
    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private string $latestMValue = '0.00';

    /**
     * @var int $maxCopies the maximum number of copies of this card that can be in a deck, sometimes overriden by oracle text. -1 for infinite.
     */
    #[Assert\NotNull]
    #[ORM\Column(type: Types::INTEGER)]
    private int $maxCopies = 1;

    #[Assert\Choice(choices: ['printed', 'funny', 'eternal', 'modern', 'pioneer', 'standard', 'unranked'])]
    #[Assert\Length(max: 20)]
    #[Assert\NotBlank]
    #[ORM\Column(length: 20)]
    private string $maximumTimelineLegality = 'printed';

    public function __construct()
    {
        parent::__construct();
        $this->firstPrintedAt = new DateTimeImmutable();
    }

    public function getCEDHRank(): int
    {
        return $this->CEDHRank;
    }

    /**
     * @return list<string>
     */
    public function getColorIdentity(): array
    {
        return $this->colorIdentity;
    }

    public function getDuelRank(): int
    {
        return $this->duelRank;
    }

    public function getFFARank(): int
    {
        return $this->FFARank;
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

    public function getImageURL(): string
    {
        return $this->imageURL;
    }

    /**
     * @return numeric-string
     */
    public function getLatestMValue(): string
    {
        return $this->latestMValue;
    }

    public function getMTGOPrice(): string
    {
        return $this->MTGOPrice;
    }

    public function getMTGOPriceAsFloat(): float
    {
        return (float)$this->MTGOPrice;
    }

    public function getMValueAsFloat(): float
    {
        return (float)$this->MValueTrend;
    }

    public function getMValueCount(): int
    {
        return $this->MValueCount;
    }

    /**
     * @return numeric-string
     */
    public function getMValueTrend(): string
    {
        return $this->MValueTrend;
    }

    public function getMaxCopies(): int
    {
        return $this->maxCopies;
    }

    public function getMaximumTimelineLegality(): string
    {
        return $this->maximumTimelineLegality;
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

    public function isDigitalOnly(): bool
    {
        return $this->isDigitalOnly;
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

    public function setCEDHRank(int $CEDHRank): self
    {
        $this->CEDHRank = $CEDHRank;

        return $this;
    }

    /**
     * @param list<string> $colorIdentity
     */
    public function setColorIdentity(array $colorIdentity): static
    {
        $this->colorIdentity = $colorIdentity;

        return $this;
    }

    public function setDuelRank(int $duelRank): self
    {
        $this->duelRank = $duelRank;

        return $this;
    }

    public function setFFARank(int $FFARank): self
    {
        $this->FFARank = $FFARank;

        return $this;
    }

    public function setFirstPrintedAt(DateTimeImmutable $firstPrintedAt): static
    {
        $this->firstPrintedAt = $firstPrintedAt;

        return $this;
    }

    public function setFirstPrintedSetCode(string $firstPrintedSetCode): static
    {
        $this->firstPrintedSetCode = $firstPrintedSetCode;

        return $this;
    }

    public function setFirstPrintedYear(int $firstPrintedYear): static
    {
        $this->firstPrintedYear = $firstPrintedYear;

        return $this;
    }

    public function setImageURL(string $imageURL): self
    {
        $this->imageURL = $imageURL;

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

    public function setIsDigitalOnly(bool $isDigitalOnly): self
    {
        $this->isDigitalOnly = $isDigitalOnly;

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

    /**
     * @param numeric-string $latestMValue
     *
     * @return static
     */
    public function setLatestMValue(string $latestMValue): static
    {
        $this->latestMValue = $latestMValue;

        return $this;
    }

    /**
     * @param numeric-string $MTGOPrice
     *
     * @return static
     */
    public function setMTGOPrice(string $MTGOPrice): static
    {
        $this->MTGOPrice = bcadd($MTGOPrice, '0', 2);

        return $this;
    }

    public function setMValueCount(int $MValueCount): static
    {
        $this->MValueCount = $MValueCount;

        return $this;
    }

    /**
     * @param numeric-string $MValueTrend
     *
     * @return static
     */
    public function setMValueTrend(string $MValueTrend): static
    {
        $this->MValueTrend = bcadd($MValueTrend, '0', 2);

        return $this;
    }

    public function setMaxCopies(int $maxCopies): self
    {
        $this->maxCopies = $maxCopies;

        return $this;
    }

    public function setMaximumTimelineLegality(string $maximumTimelineLegality): static
    {
        $this->maximumTimelineLegality = $maximumTimelineLegality;

        return $this;
    }

    /**
     * This method takes multiple prices as parameters, then:
     * - updates the latest MValue with the averate of the new prices
     * - updates the MValueCount with the new count of prices, shifting it if necessary
     * - updates the MValueTrend with the new trend of the average
     *
     * @param numeric-string $newPriceAverage the new prices to take into account
     *
     * @return static
     */
    public function updateMValueWithNewPrice(string $newPriceAverage): static
    {
        // First, save this latest value
        $this->setLatestMValue($newPriceAverage);

        // Only update the prices if the latest price found is > 0.0
        if ((float)$this->latestMValue > 0.0) {

            // Second, update the count and trend
            // Update the new total
            // If we have too many prices already...
            if ($this->MValueCount >= static::PRICE_RETENTION_COUNT) {
                $this->MValueCount = static::PRICE_RETENTION_COUNT;
            } else {
                ++$this->MValueCount;
            }

            $this->MValueTrend = bcdiv(
                bcadd(
                    bcmul(
                        (string)($this->MValueCount - 1),
                        $this->MValueTrend,
                        4
                    ),
                    $this->getLatestMValue(),
                    4
                ),
                (string)$this->MValueCount,
                4
            ); // extra precision during calc
        }

        return $this;
    }
}
