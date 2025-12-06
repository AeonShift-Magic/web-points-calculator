<?php

declare(strict_types = 1);

namespace App\Entity;

use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\HasLifecycleCallbacks]
#[ORM\MappedSuperclass]
abstract class AbstractSourceActivityHistory implements SourceActivityHistoryInterface
{
    use HistoryTrackableEntityTrait {
        HistoryTrackableEntityTrait::__construct as private __traitConstruct;
    }

    #[Assert\Length(max: 100)]
    #[Assert\NotBlank]
    #[ORM\Column(length: 100)]
    private string $channel = '';

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?DateTime $endedAt = null;

    #[Assert\Length(max: 255)]
    #[Assert\NotNull]
    #[ORM\Column(length: 255)]
    private string $errorSummary = '';

    #[ORM\Column]
    #[ORM\GeneratedValue]
    #[ORM\Id]
    private ?int $id = null;

    #[Assert\Length(max: 255)]
    #[Assert\NotBlank]
    #[ORM\Column(length: 255)]
    private string $logFilePath = '';

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?DateTime $startedAt = null;

    #[Assert\Choice(choices: ['cli', 'http', 'cron'])]
    #[Assert\Length(max: 50)]
    #[Assert\NotBlank]
    #[ORM\Column(length: 50)]
    private string $startedFrom = '';

    #[Assert\Length(max: 255)]
    #[Assert\NotNull]
    #[ORM\Column(length: 255)]
    private string $successSummary = '';

    #[Assert\Length(max: 30)]
    #[Assert\NotBlank]
    #[ORM\Column(length: 30)]
    private string $uniqueId;

    public function __construct(string $channel = '')
    {
        $this->__traitConstruct();
        $this->uniqueId = uniqid($channel, true);
    }

    public function getChannel(): string
    {
        return $this->channel;
    }

    public function getEndedAt(): ?DateTime
    {
        return $this->endedAt;
    }

    public function getErrorSummary(): string
    {
        return $this->errorSummary;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLogFilePath(): string
    {
        return $this->logFilePath;
    }

    public function getStartedAt(): ?DateTime
    {
        return $this->startedAt;
    }

    public function getStartedFrom(): string
    {
        return $this->startedFrom;
    }

    public function getSuccessSummary(): string
    {
        return $this->successSummary;
    }

    public function getUniqueId(): string
    {
        return $this->uniqueId;
    }

    public function setChannel(string $channel): self
    {
        $this->channel = $channel;

        return $this;
    }

    public function setEndedAt(?DateTime $endedAt): self
    {
        $this->endedAt = $endedAt;

        return $this;
    }

    public function setErrorSummary(string $errorSummary): self
    {
        $this->errorSummary = $errorSummary;

        return $this;
    }

    public function setId(?int $id): self
    {
        $this->id = $id;

        return $this;
    }

    public function setLogFilePath(string $logFilePath): self
    {
        $this->logFilePath = $logFilePath;

        return $this;
    }

    public function setStartedAt(?DateTime $startedAt): self
    {
        $this->startedAt = $startedAt;

        return $this;
    }

    public function setStartedFrom(string $startedFrom): self
    {
        $this->startedFrom = $startedFrom;

        return $this;
    }

    public function setSuccessSummary(string $successSummary): self
    {
        $this->successSummary = $successSummary;

        return $this;
    }

    public function setUniqueId(string $uniqueId): self
    {
        $this->uniqueId = $uniqueId;

        return $this;
    }
}
