<?php

declare(strict_types = 1);

namespace App\Twig;

use App\Entity\CustomBlock;
use Doctrine\ORM\EntityManagerInterface;
use Override;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

final class CustomBlockExtension extends AbstractExtension
{
    public function __construct(private EntityManagerInterface $entityManager, private Environment $twig)
    {
    }

    public function getBlocks(string $blockKey): string
    {
        /** @var array<CustomBlock> $customBlocks */
        $customBlocks = $this->entityManager->getRepository(CustomBlock::class)->findBy(['blockKey' => $blockKey], ['weight' => 'DESC']);
        $output = '';

        try {
            // Render a Twig template, too, in case of editing rights, to add a direct link to the edit page
            $output = $this->twig->render(
                '_custom_block_content.html.twig',
                [
                    'custom_blocks' => $customBlocks,
                ]
            );
        } catch (LoaderError|RuntimeError|SyntaxError) {
        }

        return $output;
    }

    #[Override]
    public function getFunctions(): array
    {
        return [
            // "is_safe" is used here, carefully!
            new TwigFunction('custom_blocks', [$this, 'getBlocks'], ['is_safe' => ['html']]),
        ];
    }
}
