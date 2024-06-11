<?php

declare(strict_types=1);

namespace App\Entity\Enums;

enum PlaylistRemoteTypes: string
{
    case Stream = 'stream';
    case Playlist = 'playlist';
    case Other = 'other';
}
