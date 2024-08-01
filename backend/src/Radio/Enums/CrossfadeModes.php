<?php

declare(strict_types=1);

namespace App\Radio\Enums;

enum CrossfadeModes: string
{
    case Normal = 'normal';
    case Smart = 'smart';
    case Disabled = 'none';

    public static function default(): self
    {
        return self::Normal;
    }
}
