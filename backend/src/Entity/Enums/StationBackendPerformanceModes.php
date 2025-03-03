<?php

declare(strict_types=1);

namespace App\Entity\Enums;

use OpenApi\Attributes as OA;

#[OA\Schema(type: 'string')]
enum StationBackendPerformanceModes: string
{
    case LessMemory = 'less_memory';
    case LessCpu = 'less_cpu';
    case Balanced = 'balanced';
    case Disabled = 'disabled';

    public static function default(): self
    {
        return self::Disabled;
    }
}
