<?php

declare(strict_types=1);

namespace App\Entity\Enums;

enum PlaylistSources: string
{
    case Songs = 'songs';
    case RemoteUrl = 'remote_url';
}
