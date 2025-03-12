<?php

declare(strict_types=1);

namespace App\Entity\Enums;

use OpenApi\Attributes as OA;

#[OA\Schema(type: 'string')]
enum PlaylistSources: string
{
    case Songs = 'songs';
    case RemoteUrl = 'remote_url';
}
