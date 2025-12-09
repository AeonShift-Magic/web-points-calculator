<?php

declare(strict_types = 1);

namespace App\Entity;

use DateTime;

interface SourceActivityHistoryInterface
{
    public const string SOURCE_CLI = 'cli';

    public const string SOURCE_CRON = 'cron';

    public const string SOURCE_HTTP = 'http';

    public function getChannel(): string;

    public function getEndedAt(): ?DateTime;

    public function getId(): ?int;

    public function getLogFilePath(): string;

    public function getStartedAt(): ?DateTime;

    public function getStartedFrom(): string;

    public function getUniqueId(): string;

    public function setChannel(string $channel): self;

    public function setEndedAt(?DateTime $endedAt): self;

    public function setId(?int $id): self;

    public function setLogFilePath(string $logFilePath): self;

    public function setStartedAt(?DateTime $startedAt): self;

    public function setStartedFrom(string $startedFrom): self;

    public function setUniqueId(string $uniqueId): self;
}
