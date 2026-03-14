<?php

/** @noinspection DynamicInvocationViaScopeResolutionInspection */

declare(strict_types = 1);

namespace App\Tests\Twig;

use App\Model\AeonShift\PointsList\MTG\MTGPointsListManager;
use App\Twig\MTGCalculatorExtension;
use JsonException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Cache\InvalidArgumentException;
use Twig\TwigFunction;

/**
 * @internal
 *
 * @small
 */
final class MTGCalculatorExtensionTest extends TestCase
{
    public function testGetFunctionsReturnsExpectedTwigFunction(): void
    {
        $manager = self::createStub(MTGPointsListManager::class);

        $extension = new MTGCalculatorExtension($manager);

        $functions = $extension->getFunctions();

        self::assertCount(1, $functions);
        self::assertInstanceOf(TwigFunction::class, $functions[0]);
        self::assertSame('get_updates_and_points_lists_as_json', $functions[0]->getName());
    }

    /**
     * @throws InvalidArgumentException
     * @throws JsonException
     */
    public function testGetUpdatesAndPointsListsAsJSONReturnsManagerResult(): void
    {
        /** @var MockObject&MTGPointsListManager $manager */
        $manager = $this->createMock(MTGPointsListManager::class);
        $manager
            ->expects(self::once())
            ->method('getAllPointsListsAndUpdatesAsJSONArray')
            ->willReturn('{"data":[]}');

        $extension = new MTGCalculatorExtension($manager);

        $output = $extension->getUpdatesAndPointsListsAsJSON();

        self::assertSame('{"data":[]}', $output);
    }
}
