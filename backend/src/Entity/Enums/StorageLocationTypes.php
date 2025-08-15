<?php

declare(strict_types=1);

namespace App\Entity\Enums;

use OpenApi\Attributes as OA;

#[OA\Schema(type: 'string')]
enum StorageLocationTypes: string
{
    case Backup = 'backup';
    case StationMedia = 'station_media';
    case StationRecordings = 'station_recordings';
    case StationPodcasts = 'station_podcasts';
}
