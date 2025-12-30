<?php

declare(strict_types = 1);

namespace App\Model\AeonShift;

interface PointsListModelListerInterface
{
    /**
     * @return array{
     *     array{
     *         label: string,
     *         releaseDate: string,
     *         className: class-string,
     *     }
     * }|array{}
     */
    public function getPointsListModelsAsArrayForLicense(string $license): array;

    /**
     * @return array<string, class-string>
     */
    public function getPointsListModelsForForms(string $license): array;
}
