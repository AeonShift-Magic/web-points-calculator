<?php

declare(strict_types = 1);

namespace App\Twig;

use App\Entity\Page;
use Doctrine\ORM\EntityManagerInterface;
use Override;
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

final class FrontMenuExtension extends AbstractExtension
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private Environment $twig,
        private RequestStack $requestStack,
        private CacheInterface $pool
    )
    {
    }

    /**
     * @throws InvalidArgumentException
     */
    public function frontMenu(string $zone): string
    {
        $locale = $this->requestStack->getCurrentRequest()?->getLocale() ?? 'en';

        /** @var array<Page> $globalPages */
        $globalPages = $this->pool->get(key: 'as_header_menu_global_pages_' . $zone . '_' . $locale, callback: function (ItemInterface $item) use ($zone): array {
            $item->expiresAfter(3600);

            return $this->entityManager->getRepository(Page::class)->findBy(['zone' => $zone, 'language' => $this->requestStack->getCurrentRequest()?->getLocale()], ['weight' => 'DESC']);
        });

        try {
            // Render a Twig template, too, in case of editing rights, to add a direct link to the edit page
            $output = $this->twig->render(
                'front/_front_menu.html.twig',
                [
                    'locale'                     => $this->requestStack->getCurrentRequest()?->getLocale(),
                    'global_pages'               => $globalPages,
                    'zone'                       => $zone,
                ]
            );
        } catch (LoaderError|RuntimeError|SyntaxError) {
            return '';
        }

        return $output;
    }

    #[Override]
    public function getFunctions(): array
    {
        return [
            // "is_safe" is used here, carefully!
            new TwigFunction('front_menu', [$this, 'frontMenu'], ['is_safe' => ['html']]),
        ];
    }
}
