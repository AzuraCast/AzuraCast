<?php

declare(strict_types=1);

namespace App\OpenApi;

use OpenApi\Analysis;
use OpenApi\Annotations as OA;
use OpenApi\Generator;
use OpenApi\Processors\Concerns\TypesTrait;
use ReflectionEnum;
use ReflectionNamedType;
use UnitEnum;

/**
 * Adds `x-enumNames` extension to backed enums.
 */
class AddXEnumNames
{
    use TypesTrait;

    public function __invoke(Analysis $analysis): void
    {
        $this->expandContextEnum($analysis);
    }

    protected function expandContextEnum(Analysis $analysis): void
    {
        /** @var OA\Schema[] $schemas */
        $schemas = $analysis->getAnnotationsOfType(OA\Schema::class, true);

        foreach ($schemas as $schema) {
            if ($schema->_context?->is('enum')) {
                /** @var class-string<UnitEnum> $fqn */
                $fqn = $schema->_context->fullyQualifiedName($schema->_context->enum);
                $re = new ReflectionEnum($fqn);
                if (!$re->isBacked()) {
                    continue;
                }

                $schemaType = $schema->type;
                $enumType = null;

                $backingType = $re->getBackingType();
                if ($backingType instanceof ReflectionNamedType) {
                    $enumType = $backingType->getName();
                }

                // no (or invalid) schema type means name
                $useName = Generator::isDefault($schemaType)
                    || ($enumType && $this->native2spec($enumType) != $schemaType);

                if (!$useName) {
                    $schemaX = Generator::isDefault($schema->x) ? [] : $schema->x;
                    $schemaX['enumNames'] = array_map(function ($case) {
                        return $case->name;
                    }, $re->getCases());

                    $schema->x = $schemaX;
                }
            }
        }
    }
}
