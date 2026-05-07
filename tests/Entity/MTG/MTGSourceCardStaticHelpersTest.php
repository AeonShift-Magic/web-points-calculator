<?php

declare(strict_types = 1);

namespace App\Tests\Entity\MTG;

use App\Entity\MTG\MTGSourceCard;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @small
 */
final class MTGSourceCardStaticHelpersTest extends TestCase
{
    public static function partnerTypeProvider(): array
    {
        return [
            'exact lowercase'          => ['partner_type_choose_a_background', true],
            'uppercase'                => ['PARTNER_TYPE_GENERIC', true],
            'mixed case'               => ['Partner_Type_Special', true],
            'no prefix - partner'      => ['partner', false],
            'no prefix - partner with' => ['partner with', false],
            'unrelated string'         => ['some ability text', false],
            'empty'                    => ['', false],
        ];
        // mb_stristr is case-insensitive and should match any occurrence of "partner_type_"
    }

    public function testStaticHasChooseABackgroundDetectsPhrase(): void
    {
        self::assertTrue(MTGSourceCard::staticHasChooseABackground('Choose a Background'));
        self::assertTrue(MTGSourceCard::staticHasChooseABackground('Some text ... choose a background ... end'));
        self::assertTrue(MTGSourceCard::staticHasChooseABackground('This card lets you CHOOSE A BACKGROUND.'));
    }

    public function testStaticHasChooseABackgroundReturnsFalseWhenAbsent(): void
    {
        self::assertFalse(MTGSourceCard::staticHasChooseABackground('No special ability here.'));
        self::assertFalse(MTGSourceCard::staticHasChooseABackground('Partner with X'));
    }

    public function testStaticHasDoctorsCompanionDetectsWithStraightAndCurlyApostrophes(): void
    {
        // Straight apostrophe
        self::assertTrue(MTGSourceCard::staticHasDoctorsCompanion("Doctor's Companion"));
        // Curly apostrophe should be normalized internally
        self::assertTrue(MTGSourceCard::staticHasDoctorsCompanion('Doctor’s Companion'));
        // Mixed case
        self::assertTrue(MTGSourceCard::staticHasDoctorsCompanion("... DOCTOR'S COMPANION ..."));
    }

    public function testStaticHasDoctorsCompanionReturnsFalseWhenAbsent(): void
    {
        self::assertFalse(MTGSourceCard::staticHasDoctorsCompanion('Some unrelated oracle text.'));
        self::assertFalse(MTGSourceCard::staticHasDoctorsCompanion('Choose a Background'));
    }

    #[DataProvider('partnerTypeProvider')]
    public function testStaticHasPartnerType(string $input, bool $expected): void
    {
        self::assertSame($expected, MTGSourceCard::staticHasPartnerType($input));
    }

    public function testStaticIsABackgroundDetectsType(): void
    {
        self::assertTrue(MTGSourceCard::staticIsABackground('Legendary Enchantment — Background'));
        self::assertTrue(MTGSourceCard::staticIsABackground('... BACKGROUND ...'));
        self::assertFalse(MTGSourceCard::staticIsABackground('Legendary Enchantment — Class'));
        self::assertFalse(MTGSourceCard::staticIsABackground(''));
    }

    public function testStaticIsADoctorDetectsType(): void
    {
        self::assertFalse(MTGSourceCard::staticIsADoctor('Legendary Creature — Doctor'));
        self::assertTrue(MTGSourceCard::staticIsADoctor('Legendary Creature — Time Lord Doctor'));
        self::assertFalse(MTGSourceCard::staticIsADoctor('... DOCTOR ...'));
        self::assertFalse(MTGSourceCard::staticIsADoctor('Legendary Creature — Wizard'));
        self::assertFalse(MTGSourceCard::staticIsADoctor(''));
    }
}
