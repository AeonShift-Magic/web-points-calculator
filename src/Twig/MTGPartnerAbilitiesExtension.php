<?php

declare(strict_types = 1);

namespace App\Twig;

use const MB_CASE_TITLE;
use Override;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

final class MTGPartnerAbilitiesExtension extends AbstractExtension
{
    #[Override]
    public function getFunctions(): array
    {
        return [
            // "is_safe" is used here, carefully!
            new TwigFunction('partner_ability_format', $this->partnerAbilityFormat(...), ['is_safe' => ['html']]),
        ];
    }

    /**
     * @param string $sourcePartnerString
     *
     * @return string
     */
    public function partnerAbilityFormat(string $sourcePartnerString): string
    {
        if (preg_match('/partner_type_(.*)/i', $sourcePartnerString, $matches)) {
            return 'Partner&mdash;[' . mb_convert_case(str_replace('_', ' ', $matches[1]), MB_CASE_TITLE) . ']';
        }

        return match ($sourcePartnerString) {
            'choose_a_background' => 'Choose a Background + Background',
            'doctors_companion'   => 'Doctor\'s Companion + The Doctor',
            'partner'             => 'Partner',
            'partner_with'        => 'Partner With',
            default               => mb_ucfirst($sourcePartnerString),
        };
    }
}
