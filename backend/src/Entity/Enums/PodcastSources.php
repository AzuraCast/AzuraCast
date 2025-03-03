<?php

declare(strict_types=1);

namespace App\Entity\Enums;

use OpenApi\Attributes as OA;

#[OA\Schema(type: 'string')]
enum PodcastSources: string
{
    case Manual = 'manual';
    case Playlist = 'playlist';
}
