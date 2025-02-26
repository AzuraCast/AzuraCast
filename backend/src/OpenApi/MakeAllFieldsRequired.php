<?php

declare(strict_types=1);

namespace App\OpenApi;

use OpenApi\Analysis;
use OpenApi\Annotations as OA;
use OpenApi\Generator;

/**
 * Expand '*' in the `required` field to be every field in the schema.
 */
class MakeAllFieldsRequired
{
    public function __invoke(Analysis $analysis): void
    {
        /** @var OA\Schema[] $schemas */
        $schemas = $analysis->getAnnotationsOfType(OA\Schema::class, true);

        foreach ($schemas as $schema) {
            if ($schema->required === ['*']) {
                $schema->required = $this->getPropertyNames($schema);
            }
        }
    }

    protected function getPropertyNames(OA\Schema $schema): array
    {
        $propertyNames = [];

        if (!Generator::isDefault($schema->allOf)) {
            foreach ($schema->allOf as $item) {
                $propertyNames = array_merge($propertyNames, $this->getPropertyNames($item));
            }
        }

        if (!Generator::isDefault($schema->properties)) {
            foreach ($schema->properties as $property) {
                if (!Generator::isDefault($property->property)) {
                    $propertyNames[] = $property->property;
                }
            }
        }

        return $propertyNames;
    }
}
