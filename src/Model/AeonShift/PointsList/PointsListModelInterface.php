<?php

declare(strict_types = 1);

namespace App\Model\AeonShift\PointsList;

use App\Entity\PointsListInterface;
use App\Repository\SourceItemsRepositoryInterface;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Contracts\Translation\TranslatorInterface;

interface PointsListModelInterface
{
    /** Mostly used for admin forms. */
    public const string LABEL = 'Original';

    /** @var string self-identifier for model grouping per license */
    public const string LICENSE = 'MTG';

    /** Must be YYYY-MM-DD, mostly used for sorting and admin forms. */
    public const string RELEASE_DATE = '2026-01-26';

    /** @var string always set at 20:00 */
    public const string RELEASE_TIME = '20:00';

    /** @var string awlays set at Paris TZ (UTC+1) */
    public const string RELEASE_TIMEZONE = 'Europe/Paris';

    /** @var int bump this everytime the base rules need a new, different model with a new class */
    public const int VERSION = 1;

    public function __construct(
        EntityManagerInterface $entityManager,
        TranslatorInterface $translator,
        SourceItemsRepositoryInterface $serviceEntityRepository,
        Security $security
    );

    /**
     * Mostly used for admin forms.
     *
     * @return string
     */
    public function __toString(): string;

    /**
     * @return string a static name generator for this classe, based on its constant properties
     */
    public static function getName(): string;

    /**
     * Simply return the static string of the release date and time.
     *
     * @return DateTime
     */
    public static function getReleaseDateAsDateTime(): DateTime;

    /**
     * This is the mandatory method used to extract current Points List values as a CSV file.
     *
     * @param PointsListInterface $pointsList the points list to generate from
     *
     * @return StreamedResponse
     */
    public function generateCSVResponseForList(PointsListInterface $pointsList): StreamedResponse;

    /**
     * This is the mandatory methode used to import, parse and validate
     * an uploaded CSV file as the new, overwritten Points for a list.
     *
     * @param string $csvSourceString usually the file contents from an uploaded file
     * @param PointsListInterface $pointsList the target points list to update
     *
     * @return array{
     *     status: string,
     *     message: string
     * }
     */
    public function processCSVString(string $csvSourceString, PointsListInterface $pointsList): array;
}
