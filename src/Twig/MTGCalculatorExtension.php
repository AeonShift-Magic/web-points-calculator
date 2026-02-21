<?php

declare(strict_types = 1);

namespace App\Twig;

use App\Model\AeonShift\PointsList\MTG\MTGPointsListManager;
use JsonException;
use Override;
use Psr\Cache\InvalidArgumentException;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

final class MTGCalculatorExtension extends AbstractExtension
{
    public function __construct(private MTGPointsListManager $pointsList)
    {
    }

    #[Override]
    public function getFunctions(): array
    {
        return [
            // "is_safe" is used here, carefully!
            new TwigFunction('get_updates_and_points_lists_as_json', $this->getUpdatesAndPointsListsAsJSON(...), ['is_safe' => ['html']]),
        ];
    }

    /**
     * @throws InvalidArgumentException|JsonException
     *
     * @return string
     */
    public function getUpdatesAndPointsListsAsJSON(): string
    {
        return $this->pointsList->getAllPointsListsAndUpdatesAsJSONArray();
    }
}
