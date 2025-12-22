<?php

declare(strict_types = 1);

namespace App\Model;

use Override;
use Symfony\Contracts\Translation\TranslatableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * The current status of each event.
 */
enum UserStatusEnum: string implements TranslatableInterface
{
    /**
     * This returns an array of choices with a valid form for validation.
     *
     * @return array<int, string>
     */
    public static function getEnumAsChoices(): array
    {
        return array_map(static fn ($case) => $case->value, self::cases());
    }

    #[Override]
    public function trans(TranslatorInterface $translator, ?string $locale = null): string
    {
        // Translate enum using custom labels
        return match ($this) {
            self::blocked   => $translator->trans('user.status.blocked.label', locale: $locale),
            self::deleted   => $translator->trans('user.status.deleted.label', locale: $locale),
            self::pending   => $translator->trans('user.status.pending.label', locale: $locale),
            self::validated => $translator->trans('user.status.validated.label', locale: $locale),
        };
    }

    // This user has been blocked
    case blocked = 'Blocked';

    // This user has been deleted
    case deleted = 'Deleted';

    // This user has asked to register on the website
    case pending = 'Pending';

    // This user has status their registration
    case validated = 'Validated';
}
