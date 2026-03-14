<?php

/** @noinspection DynamicInvocationViaScopeResolutionInspection */

declare(strict_types = 1);

namespace App\Tests\Twig;

use App\Entity\Page;
use App\Twig\FrontMenuExtension;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\TwigFunction;

/**
 * @internal
 *
 * @small
 */
final class FrontMenuExtensionTest extends TestCase
{
    /**
     * @throws InvalidArgumentException
     */
    public function testFrontMenuRendersMenu(): void
    {
        $page = new Page();

        $repo = self::createStub(EntityRepository::class);
        $repo->method('findBy')->willReturn([$page]);

        $entityManager = self::createStub(EntityManagerInterface::class);
        $entityManager->method('getRepository')->willReturn($repo);

        $request = new Request();
        $request->setLocale('en');

        $requestStack = new RequestStack();
        $requestStack->push($request);

        $cacheItem = self::createStub(ItemInterface::class);
        $cacheItem->method('expiresAfter')->willReturnSelf();

        $pool = self::createMock(CacheInterface::class);
        $pool
            ->expects(self::once())
            ->method('get')
            ->willReturnCallback(static function (string $key, callable $callback) use ($cacheItem) {
                return $callback($cacheItem);
            });

        /** @var Environment&MockObject $twig */
        $twig = $this->createMock(Environment::class);
        $twig
            ->expects(self::once())
            ->method('render')
            ->with(
                'front/_front_menu.html.twig',
                [
                    'locale'       => 'en',
                    'global_pages' => [$page],
                    'zone'         => 'header',
                ]
            )
            ->willReturn('<nav>menu</nav>');

        $extension = new FrontMenuExtension($entityManager, $twig, $requestStack, $pool);

        $output = $extension->frontMenu('header');

        self::assertSame('<nav>menu</nav>', $output);
    }

    /**
     * @throws InvalidArgumentException
     */
    public function testFrontMenuReturnsEmptyStringOnTwigException(): void
    {
        $repo = self::createStub(EntityRepository::class);
        $repo->method('findBy')->willReturn([]);

        $entityManager = self::createStub(EntityManagerInterface::class);
        $entityManager->method('getRepository')->willReturn($repo);

        $request = new Request();
        $request->setLocale('en');

        $requestStack = new RequestStack();
        $requestStack->push($request);

        $cacheItem = self::createStub(ItemInterface::class);
        $cacheItem->method('expiresAfter')->willReturnSelf();

        $pool = self::createMock(CacheInterface::class);
        $pool
            ->expects(self::once())
            ->method('get')
            ->willReturnCallback(static function (string $key, callable $callback) use ($cacheItem) {
                return $callback($cacheItem);
            });

        /** @var Environment&MockObject $twig */
        $twig = $this->createMock(Environment::class);
        $twig
            ->expects(self::once())
            ->method('render')
            ->willThrowException(new LoaderError('template not found'));

        $extension = new FrontMenuExtension($entityManager, $twig, $requestStack, $pool);

        $output = $extension->frontMenu('header');

        self::assertSame('', $output);
    }

    public function testGetFunctionsReturnsFrontMenuFunction(): void
    {
        $entityManager = self::createStub(EntityManagerInterface::class);
        $twig = self::createStub(Environment::class);
        $requestStack = new RequestStack();
        $pool = self::createStub(CacheInterface::class);

        $extension = new FrontMenuExtension($entityManager, $twig, $requestStack, $pool);

        $functions = $extension->getFunctions();

        self::assertCount(1, $functions);
        self::assertInstanceOf(TwigFunction::class, $functions[0]);
        self::assertSame('front_menu', $functions[0]->getName());
    }
}
