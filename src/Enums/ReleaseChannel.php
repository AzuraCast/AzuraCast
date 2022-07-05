<?php

declare(strict_types=1);

namespace App\Enums;

enum ReleaseChannel: string
{
    case RollingRelease = 'latest';
    case Stable = 'stable';

    public static function default(): self
    {
        return self::RollingRelease;
    }
}
