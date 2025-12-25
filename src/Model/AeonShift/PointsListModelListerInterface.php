<?php

declare(strict_types = 1);

namespace App\Model\AeonShift;

interface PointsListModelListerInterface
{
    /**
     * @return array{
     *     label: string,
     *     releaseDate: string,
     *     className: class-string,
     * }
     */
    public function getPointsListModelsAsArray(): array;

    /**
     * @return array<string, string>
     */
    public function getPointsListModelsForForms(): array;
}
