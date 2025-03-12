<?php

declare(strict_types=1);

namespace App\Enums;

use OpenApi\Attributes as OA;

#[OA\Schema(type: 'string')]
enum ReleaseChannel: string
{
    case RollingRelease = 'latest';
    case Stable = 'stable';

    public static function default(): self
    {
        return self::RollingRelease;
    }
}
