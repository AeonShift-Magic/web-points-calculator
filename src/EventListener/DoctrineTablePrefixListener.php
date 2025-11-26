<?php

declare(strict_types = 1);

namespace App\EventListener;

use Doctrine\ORM\Event\LoadClassMetadataEventArgs;
use Doctrine\ORM\Mapping\ClassMetadata;

/**
 * Adds prefixes to all Doctrine-generated tables in database.
 */
final class DoctrineTablePrefixListener
{
    public function __construct(private string $prefix = '')
    {
    }

    public function loadClassMetadata(LoadClassMetadataEventArgs $eventArgs): void
    {
        $classMetadata = $eventArgs->getClassMetadata();

        if (! $classMetadata->isInheritanceTypeSingleTable() || $classMetadata->getName() === $classMetadata->rootEntityName) {
            $classMetadata->setPrimaryTable([
                'name' => $this->prefix . $classMetadata->getTableName(),
            ]);
        }

        /**
         * @var array<string, mixed> $mapping
         */
        foreach ($classMetadata->getAssociationMappings() as $fieldName => $mapping) {
            if (
                isset($mapping['type'], $mapping['isOwningSide'], $mapping['joinTable']) && $mapping['type'] === ClassMetadata::MANY_TO_MANY && $mapping['isOwningSide'] && is_object($mapping['joinTable']) && property_exists($mapping['joinTable'], 'name')
            ) {
                /** @var string $mappedTableName */
                $mappedTableName = $mapping['joinTable']->name;
                if (isset($classMetadata->associationMappings[$fieldName]['joinTable']) && is_array($classMetadata->associationMappings[$fieldName]['joinTable'])) {
                    $classMetadata->associationMappings[$fieldName]['joinTable']['name'] = $this->prefix . $mappedTableName;
                }
            }
        }
    }
}
