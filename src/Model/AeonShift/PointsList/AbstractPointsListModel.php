<?php

declare(strict_types = 1);

namespace App\Model\AeonShift\PointsList;

use App\Entity\PointsListInterface;
use DateTime;
use DateTimeZone;
use Override;
use Stringable;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\String\UnicodeString;

abstract class AbstractPointsListModel implements PointsListModelInterface, Stringable
{
    #[Override]
    public function __toString(): string
    {
        return static::getName();
    }

    #[Override]
    abstract public function generateCSVResponseForList(PointsListInterface $pointsList): StreamedResponse;

    #[Override]
    abstract public function processCSVString(string $csvSourceString, PointsListInterface $pointsList): array;

    #[Override]
    public static function getName(): string
    {
        return static::LABEL . ' ' . static::getReleaseDateAsDateTime()->format('Y-m-d H:i');
    }

    #[Override]
    public static function getReleaseDateAsDateTime(): DateTime
    {
        return new DateTime(static::RELEASE_DATE . ' ' . static::RELEASE_TIME, new DateTimeZone(static::RELEASE_TIMEZONE));
    }

    public function sanitizeStringFromCSVFile(string $string): string
    {
        // Convert to UTF-8 safely
        $UTF8String = mb_convert_encoding($string, 'UTF-8', 'auto');

        // Remove any whitespace that's not a space character
        $finalString = new UnicodeString((string)$UTF8String)
            ->collapseWhitespace()
            ->lower()
            ->title(allWords: true)
            ->trim();

        // Return the sanitized string
        return $finalString->toString();
    }
}
