<?php

declare(strict_types=1);

namespace App\Utilities;

final class Types
{
    public static function string(
        mixed $input,
        string $defaultIfNull = '',
        bool $countEmptyAsNull = false
    ): string {
        return self::stringOrNull($input, $countEmptyAsNull) ?? $defaultIfNull;
    }

    public static function stringOrNull(
        mixed $input,
        bool $countEmptyAsNull = false
    ): ?string {
        if (null === $input) {
            return null;
        }

        if ($countEmptyAsNull) {
            if ('' === $input) {
                return null;
            }

            $input = trim((string)$input);
            return (!empty($input)) ? $input : null;
        }

        return (string)$input;
    }

    public static function int(mixed $input, int $defaultIfNull = 0): int
    {
        return self::intOrNull($input) ?? $defaultIfNull;
    }

    public static function intOrNull(mixed $input): ?int
    {
        if (null === $input || is_int($input)) {
            return $input;
        }

        return (is_numeric($input))
            ? (int)$input
            : null;
    }

    public static function float(mixed $input, float $defaultIfNull = 0.0): float
    {
        return self::floatOrNull($input) ?? $defaultIfNull;
    }

    public static function floatOrNull(mixed $input): ?float
    {
        if (null === $input || is_float($input)) {
            return $input;
        }

        return (is_numeric($input))
            ? (float)$input
            : null;
    }

    public static function bool(mixed $input, bool $defaultIfNull = false, bool $broadenValidBools = false): bool
    {
        return self::boolOrNull($input, $broadenValidBools) ?? $defaultIfNull;
    }

    public static function boolOrNull(mixed $input, bool $broadenValidBools = false): ?bool
    {
        if (null === $input || is_bool($input)) {
            return $input;
        }

        if (is_int($input)) {
            return 0 !== $input;
        }

        if ($broadenValidBools) {
            $value = trim((string)$input);
            return str_starts_with(strtolower($value), 'y')
                || 'true' === strtolower($value)
                || '1' === $value;
        }

        return (bool)$input;
    }

    public static function array(mixed $input, array $defaultIfNull = []): array
    {
        return self::arrayOrNull($input) ?? $defaultIfNull;
    }

    public static function arrayOrNull(mixed $input): ?array
    {
        if (null === $input || is_array($input)) {
            return $input;
        }

        return (array)$input;
    }
}
