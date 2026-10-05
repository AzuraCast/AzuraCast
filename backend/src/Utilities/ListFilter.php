<?php

declare(strict_types=1);

namespace App\Utilities;

use App\Enums\ListFilterMode;
use BackedEnum;

/**
 * A filterable list field and its allowed values & matching mode
 */
final readonly class ListFilter
{
    /**
     * @param ?list<string> $allowedValues
     */
    private function __construct(
        public string $field,
        public ListFilterMode $mode,
        public ?array $allowedValues = null
    ) {
    }

    /**
     * @param class-string<BackedEnum> $enum
     */
    public static function fromEnum(string $field, string $enum): self
    {
        $values = [];
        foreach ($enum::cases() as $case) {
            $values[] = (string) $case->value;
        }

        return new self($field, ListFilterMode::Equals, $values);
    }

    public static function contains(string $field): self
    {
        return new self($field, ListFilterMode::Contains);
    }
}
