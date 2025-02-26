<?php

declare(strict_types=1);

namespace App\TypeScript;

use ReflectionEnum;
use ReflectionEnumBackedCase;
use Spatie\TypeScriptTransformer\Structures\TransformedType;
use Spatie\TypeScriptTransformer\Transformers\EnumTransformer;

final class NativeJsEnumTransformer extends EnumTransformer
{
    protected function toEnum(ReflectionEnum $enum, string $name): TransformedType
    {
        /** @var ReflectionEnumBackedCase[] $enumCases */
        $enumCases = $enum->getCases();

        $options = array_filter(
            array_map(
                function (ReflectionEnumBackedCase $case): ?string {
                    $hiddenAttributes = $case->getAttributes(IgnoreCase::class);
                    return (empty($hiddenAttributes))
                        ? "'{$case->getName()}': {$this->toEnumValue($case)}"
                        : null;
                },
                $enumCases
            )
        );

        return TransformedType::create(
            $enum,
            $name,
            'Object.freeze({ ' . implode(', ', $options) . ' } as const)',
            keyword: 'const'
        );
    }
}
