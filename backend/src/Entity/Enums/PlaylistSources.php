<?php

declare(strict_types=1);

namespace App\Entity\Enums;

enum PlaylistSources: string
{
    case Songs = 'songs';
    case Playlists = 'playlists';
    case RemoteUrl = 'remote_url';
}
