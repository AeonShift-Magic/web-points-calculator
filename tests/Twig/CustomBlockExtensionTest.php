<?php

/** @noinspection DynamicInvocationViaScopeResolutionInspection */

declare(strict_types = 1);

namespace App\Tests\Twig;

use App\Entity\CustomBlock;
use App\Twig\CustomBlockExtension;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\TwigFunction;

/**
 * @internal
 *
 * @small
 */
final class CustomBlockExtensionTest extends TestCase
{
    public function testGetBlocksRendersBlocks(): void
    {
        $block = new CustomBlock();
        $block->setContents('<div>Hello</div>');

        $repo = self::createStub(EntityRepository::class);
        $repo->method('findBy')->willReturn([$block]);

        $entityManager = self::createStub(EntityManagerInterface::class);
        $entityManager
            ->method('getRepository')
            ->willReturn($repo);

        /** @var Environment&MockObject $twig */
        $twig = $this->createMock(Environment::class);
        $twig
            ->expects(self::once())
            ->method('render')
            ->with(
                '_custom_block_content.html.twig',
                ['custom_blocks' => [$block]]
            )
            ->willReturn('<div>Hello</div>');

        $extension = new CustomBlockExtension($entityManager, $twig);

        $output = $extension->getBlocks('test.key');

        self::assertSame('<div>Hello</div>', $output);
    }

    public function testGetBlocksReturnsEmptyStringOnTwigException(): void
    {
        $repo = self::createStub(EntityRepository::class);
        $repo->method('findBy')->willReturn([]);

        $entityManager = self::createStub(EntityManagerInterface::class);
        $entityManager
            ->method('getRepository')
            ->willReturn($repo);

        /** @var Environment&MockObject $twig */
        $twig = $this->createMock(Environment::class);
        $twig
            ->expects(self::once())
            ->method('render')
            ->willThrowException(new LoaderError('template not found'));

        $extension = new CustomBlockExtension($entityManager, $twig);

        $output = $extension->getBlocks('missing');

        self::assertSame('', $output);
    }

    public function testGetFunctionsReturnsCustomBlocksFunction(): void
    {
        $entityManager = self::createStub(EntityManagerInterface::class);
        $twig = self::createStub(Environment::class);

        $extension = new CustomBlockExtension($entityManager, $twig);

        $functions = $extension->getFunctions();

        self::assertCount(1, $functions);
        self::assertInstanceOf(TwigFunction::class, $functions[0]);
        self::assertSame('custom_blocks', $functions[0]->getName());
    }
}
