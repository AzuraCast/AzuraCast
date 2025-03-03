<?php

declare(strict_types=1);

namespace App\Entity\Enums;

use OpenApi\Attributes as OA;

#[OA\Schema(type: 'string')]
enum PlaylistRemoteTypes: string
{
    case Stream = 'stream';
    case Playlist = 'playlist';
    case Other = 'other';
}
