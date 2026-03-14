<?php

declare(strict_types = 1);

namespace App\Tests\Twig;

use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Twig\Environment;
use Twig\Loader\ArrayLoader;

/**
 * Base test case for Twig extensions.
 */
abstract class TwigExtensionTestCase extends TestCase
{
    protected MockObject&EntityManagerInterface $entityManager;

    protected Environment $twig;

    protected function createTwigEnvironmentWithTemplate(string $templateName, string $templateContent): Environment
    {
        return new Environment(new ArrayLoader([$templateName => $templateContent]));
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->twig = new Environment(new ArrayLoader([]));
    }
}
