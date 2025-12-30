<?php

declare(strict_types = 1);

namespace App\Model\AeonShift;

use App\Model\AeonShift\PointsList\PointsListModelInterface;
use Override;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Cache\InvalidArgumentException;
use ReflectionClass;
use Symfony\Component\Finder\Finder;

final class PointsListModelDetectorModel implements PointsListModelListerInterface
{
    public function __construct(private string $projectDir, private CacheItemPoolInterface $cachePool)
    {
    }

    /**
     * @throws InvalidArgumentException
     *
     * @return array{
     *     array{
     *         label: string,
     *         releaseDate: string,
     *         className: class-string,
     *     }
     * }|array{}
     */
    #[Override]
    public function getPointsListModelsAsArrayForLicense(string $license): array
    {
        $cacheKey = 'points_list_models_' . $license;
        $cacheItem = $this->cachePool->getItem($cacheKey);

        if ($cacheItem->isHit()) {
            /** @var array{
             *     array{
             *         label: string,
             *         releaseDate: string,
             *         className: class-string,
             *     }
             * }|array{} $cacheValue
             */
            $cacheValue = $cacheItem->get();

            return $cacheValue;
        }

        $models = $this->generatePointsListModelsForLicense($license);

        $cacheItem->set($models);
        $cacheItem->expiresAfter(600);
        $this->cachePool->save($cacheItem);

        return $models;
    }

    /**
     * @throws InvalidArgumentException
     *
     * @return array<string, class-string>
     */
    #[Override]
    public function getPointsListModelsForForms(string $license): array
    {
        $models = $this->getPointsListModelsAsArrayForLicense($license);
        $formChoices = [];

        foreach ($models as $model) {
            $formChoices[$model['label']] = $model['className'];
        }

        return $formChoices;
    }

    /**
     * @return array{
     *      array{
     *          label: string,
     *          releaseDate: string,
     *          className: class-string,
     *      }
     * }|array{}
     */
    private function generatePointsListModelsForLicense(string $license): array
    {
        $models = [];
        $pointsListDir = $this->projectDir . '/src/Model/AeonShift/PointsList';

        if (! is_dir($pointsListDir)) {
            return $models;
        }

        $finder = new Finder();
        $finder->files()->in($pointsListDir)->name('*.php')->depth('>= 1');

        foreach ($finder as $file) {
            $relativePath = mb_substr($file->getPathname(), mb_strlen($this->projectDir) + 1);
            /** @var class-string<PointsListModelInterface> $className */
            $className = 'App\\' . str_replace(['src/', '/', '.php'], ['', '\\', ''], $relativePath);

            if (! class_exists($className)) {
                continue;
            }

            $reflection = new ReflectionClass($className);

            // Check if class implements PointsListModelInterface
            if (! $reflection->implementsInterface(PointsListModelInterface::class)) {
                continue;
            }

            // Check if class has LICENSE constant and matches the requested license
            if (! $reflection->hasConstant('LICENSE') || $reflection->getConstant('LICENSE') !== $license) {
                continue;
            }

            // Extract constants
            $label = $className::getName();
            /** @var string $releaseDate */
            $releaseDate = $reflection->hasConstant('RELEASE_DATE') ? $reflection->getConstant('RELEASE_DATE') : '';

            $models[] = [
                'label'       => $label,
                'releaseDate' => $releaseDate,
                'className'   => $className,
            ];
        }

        // Sort by release date
        usort($models, static function ($a, $b) {
            return strcmp($b['releaseDate'], $a['releaseDate']);
        });

        return $models;
    }
}
