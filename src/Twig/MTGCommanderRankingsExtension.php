<?php

declare(strict_types = 1);

namespace App\Twig;

use Override;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

final class MTGCommanderRankingsExtension extends AbstractExtension
{
    #[Override]
    public function getFunctions(): array
    {
        return [
            // "is_safe" is used here, carefully!
            new TwigFunction('ranking_decorator', $this->rankingDecorator(...), ['is_safe' => ['html']]),
            new TwigFunction('ranking_class', $this->rankingClass(...), ['is_safe' => ['html']]),
        ];
    }

    /**
     * Returns the ranking class based on the ranking value as a string.
     *
     * top: 'Top Commanders Only' (top 10%)
     * popular: 'Top and Popular Commanders' (top 10-50%)
     * alltournamentranked: 'All Tournament Ranked Commanders' (whatever has a value)
     * all: 'All Commanders' (the rest)
     *
     * @param float|int|string $ranking
     * @param float|int|string $rankRange
     *
     * @return string
     */
    public function rankingClass(string|int|float $ranking, string|int|float $rankRange = 750): string
    {
        $ranking = (int)$ranking;
        $rankRange = max(1, (int)$rankRange);

        if ($ranking <= 0 || $ranking > $rankRange) {
            return 'all';
        }

        // Power-law cutoffs calibrated to your targets
        $topCutoff = 0.3768 * ($rankRange ** 0.7387);
        $popularCutoff = 1.554 * ($rankRange ** 0.6901);

        if ($ranking <= $topCutoff) {
            return 'top';
        }

        if ($ranking <= $popularCutoff) {
            return 'popular';
        }

        return 'alltournamentranked';
    }

    /**
     * @param float|int|string $ranking
     * @param int $rankRange
     *
     * @return string
     */
    public function rankingDecorator(string|int|float $ranking, int $rankRange = 500): string
    {
        $normalizedRanking = (int)$ranking;

        if ($normalizedRanking <= 0) {
            return '';
        }

        return '<span title="#' . $normalizedRanking . '" class="card-ranking card-ranking-' . $this->rankingClass($ranking, $rankRange) . '">#' . $normalizedRanking . '</span>';
    }
}
