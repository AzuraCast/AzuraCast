<?php

declare(strict_types=1);

namespace App\Entity\Enums;

enum PlaylistTypes: string
{
    case Standard = 'default';
    case OncePerXSongs = 'once_per_x_songs';
    case OncePerXMinutes = 'once_per_x_minutes';
    case OncePerHour = 'once_per_hour';
    case Advanced = 'custom';

    public static function default(): self
    {
        return self::Standard;
    }
}
