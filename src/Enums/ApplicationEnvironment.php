<?php

declare(strict_types=1);

namespace App\Enums;

enum ApplicationEnvironment: string
{
    case Production = 'production';
    case Testing = 'testing';
    case Development = 'development';

    public function getName(): string
    {
        return ucfirst($this->value);
    }

    public static function default(): self
    {
        return self::Production;
    }

    public static function toSelect(): array
    {
        $values = [];
        foreach (self::cases() as $case) {
            $values[$case->value] = $case->getName();
        }
        return $values;
    }
}
