<?php

declare(strict_types = 1);

namespace App\Twig;

use Override;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

final class MTGColorsExtension extends AbstractExtension
{
    /**
     * @param array<string> $colorIdentityArray
     *
     * @return string
     */
    public function colorIdentityArrayToBalls(array $colorIdentityArray): string
    {
        $outputString = '';

        foreach ($colorIdentityArray as $colorIdentity) {
            $outputString .= '<span title="{' . $colorIdentity . '}" class="color-identity color-identity-' . mb_strtolower($colorIdentity) . '">{' . $colorIdentity . '}</span>';
        }

        return $outputString;
    }

    #[Override]
    public function getFunctions(): array
    {
        return [
            // "is_safe" is used here, carefully!
            new TwigFunction('color_identity_array_to_balls', $this->colorIdentityArrayToBalls(...), ['is_safe' => ['html']]),
        ];
    }
}
