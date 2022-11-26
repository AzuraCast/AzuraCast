<?php

declare(strict_types=1);

namespace App\Entity\Enums;

enum WebhookTriggers: string
{
    case All = 'all';

    case SongChanged = 'song_changed';
    case ListenerGained = 'listener_gained';
    case ListenerLost = 'listener_lost';
    case LiveConnect = 'live_connect';
    case LiveDisconnect = 'live_disconnect';
    case StationOffline = 'station_offline';
    case StationOnline = 'station_online';
}
