<?php

declare(strict_types=1);

namespace App\Entity\Enums;

enum PodcastSources: string
{
    case Manual = 'manual';
    case Playlist = 'playlist';
}
