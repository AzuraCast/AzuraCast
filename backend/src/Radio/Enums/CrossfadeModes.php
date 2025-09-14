<?php

declare(strict_types=1);

namespace App\Radio\Enums;

use OpenApi\Attributes as OA;

#[OA\Schema(type: 'string')]
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
