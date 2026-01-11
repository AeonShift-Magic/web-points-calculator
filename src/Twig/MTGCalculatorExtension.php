<?php

declare(strict_types = 1);

namespace App\Twig;

use App\Entity\MTG\MTGCardSourceActivityHistory;
use Override;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;
use const DIRECTORY_SEPARATOR;

final class MTGCalculatorExtension extends AbstractExtension
{
    public const array CHANNEL_COLORS = [
        'text-indigo-200',
        'text-lime-300',
        'text-rose-300',
        'text-green-300',
    ];

    public function __construct(private string $projectDir, private string $scryfallCardsSourceDir, private readonly UrlGeneratorInterface $urlGenerator)
    {
    }

    public function colorizeChannel(string $channelString): string
    {
        $output = '';

        foreach (explode('/', $channelString) as $i => $channelSection) {
            $output .= '<span class="' . (self::CHANNEL_COLORS[$i] ?? '') . '">' . $channelSection . '</span> ';
        }

        return $output;
    }

    public function fileLinkOrString(MTGCardSourceActivityHistory $cardSourceActivityHistory): string
    {
        $output = $cardSourceActivityHistory->getLogFilePath();
        $channelPaths = explode('/', $cardSourceActivityHistory->getChannel());

        if (
            isset($channelPaths[1], $channelPaths[2])
            && $channelPaths[0] === 'scryfall'
            && $channelPaths[1] === 'defaultmtgcards'
            && in_array($channelPaths[2], ['download', 'dbupdate'], true)
        ) {
            $path = $this->projectDir . DIRECTORY_SEPARATOR . $this->scryfallCardsSourceDir . DIRECTORY_SEPARATOR . $cardSourceActivityHistory->getLogFilePath();

            if (file_exists($path)) {
                $output = '<a href="' . $this->urlGenerator->generate('admin_mtg_card_source_activity_history_download_log', ['id' => $cardSourceActivityHistory->id]) . '" target="_blank">' . $cardSourceActivityHistory->getLogFilePath() . '</a>';
            }
        }

        return $output;
    }

    #[Override]
    public function getFunctions(): array
    {
        return [
            // "is_safe" is used here, carefully!
            new TwigFunction('colorize_channel', $this->colorizeChannel(...), ['is_safe' => ['html']]),
            new TwigFunction('file_link_or_string', $this->fileLinkOrString(...), ['is_safe' => ['html']]),
        ];
    }
}
