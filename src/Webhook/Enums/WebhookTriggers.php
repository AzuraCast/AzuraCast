<?php

declare(strict_types=1);

namespace App\Webhook\Enums;

enum WebhookTriggers: string
{
    case SongChanged = 'song_changed';
    case SongChangedLive = 'song_changed_live';
    case ListenerGained = 'listener_gained';
    case ListenerLost = 'listener_lost';
    case LiveConnect = 'live_connect';
    case LiveDisconnect = 'live_disconnect';
    case StationOffline = 'station_offline';
    case StationOnline = 'station_online';

    /**
     * @return string[] All trigger values.
     */
    public static function allTriggers(): array
    {
        return array_map(
            fn(WebhookTriggers $trigger) => $trigger->value,
            self::cases()
        );
    }

    /**
     * @return string[] All trigger values, except listener change ones.
     */
    public static function allTriggersExceptListeners(): array
    {
        return array_diff(
            self::allTriggers(),
            [
                self::ListenerGained->value,
                self::ListenerLost->value,
            ]
        );
    }
}
