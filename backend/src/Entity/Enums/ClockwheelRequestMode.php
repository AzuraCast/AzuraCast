<?php

declare(strict_types=1);

namespace App\Entity\Enums;

use OpenApi\Attributes as OA;

#[OA\Schema(type: 'string')]
enum ClockwheelRequestMode: string
{
    case None = 'none';
    case Any = 'any';
    case PlaylistOnly = 'playlist_only';
}
