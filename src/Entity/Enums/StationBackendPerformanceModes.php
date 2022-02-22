<?php

// phpcs:ignoreFile

declare(strict_types=1);

namespace App\Entity\Enums;

enum StationBackendPerformanceModes: string
{
    case LessMemory = 'less_memory';
    case LessCpu = 'less_cpu';
    case Balanced = 'balanced';

    public static function default(): self
    {
        return self::Balanced;
    }
}
