<?php

declare(strict_types=1);

namespace App\Service\PlaylistConfiguration;

enum PlaylistConfigurationType: string
{
    case STATION = 'station';
    case PLAYLIST = 'playlist';
}
