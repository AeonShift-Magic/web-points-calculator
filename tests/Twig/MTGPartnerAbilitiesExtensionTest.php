<?php

/** @noinspection DynamicInvocationViaScopeResolutionInspection */

declare(strict_types = 1);

namespace App\Tests\Twig;

use App\Twig\MTGPartnerAbilitiesExtension;
use PHPUnit\Framework\TestCase;
use Twig\TwigFunction;

/**
 * @internal
 *
 * @small
 */
final class MTGPartnerAbilitiesExtensionTest extends TestCase
{
    public function testGetFunctionsReturnsExpectedTwigFunction(): void
    {
        $extension = new MTGPartnerAbilitiesExtension();

        $functions = $extension->getFunctions();

        self::assertCount(1, $functions);
        self::assertInstanceOf(TwigFunction::class, $functions[0]);
        self::assertSame('partner_ability_format', $functions[0]->getName());
    }

    public function testPartnerAbilityFormatDefaultsToUcFirst(): void
    {
        $extension = new MTGPartnerAbilitiesExtension();

        self::assertSame('CustomString', $extension->partnerAbilityFormat('customString'));
        self::assertSame('Another_example', $extension->partnerAbilityFormat('another_example'));
    }

    public function testPartnerAbilityFormatHandlesPartnerTypePattern(): void
    {
        $extension = new MTGPartnerAbilitiesExtension();

        $input = 'partner_type_legendary_creature';
        self::assertSame('Partner&mdash;[Legendary Creature]', $extension->partnerAbilityFormat($input));
    }

    public function testPartnerAbilityFormatHandlesSpecialCases(): void
    {
        $extension = new MTGPartnerAbilitiesExtension();

        self::assertSame('Choose a Background + Background', $extension->partnerAbilityFormat('choose_a_background'));
        self::assertSame('Doctor\'s Companion + The Doctor', $extension->partnerAbilityFormat('doctors_companion'));
        self::assertSame('Partner', $extension->partnerAbilityFormat('partner'));
        self::assertSame('Partner With', $extension->partnerAbilityFormat('partner_with'));
    }
}
