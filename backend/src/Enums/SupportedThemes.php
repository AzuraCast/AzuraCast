<?php

declare(strict_types=1);

namespace App\Enums;

use OpenApi\Attributes as OA;

#[OA\Schema(type: 'string')]
enum SupportedThemes: string
{
    case Browser = 'browser';
    case Light = 'light';
    case Dark = 'dark';

    public static function default(): self
    {
        return self::Browser;
    }
}
