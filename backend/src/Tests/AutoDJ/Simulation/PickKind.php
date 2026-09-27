<?php

declare(strict_types=1);

namespace App\Tests\AutoDJ\Simulation;

/**
 * How duplicate prevention settled a build, read from the log output of that build
 */
enum PickKind: string
{
    case Strict = 'strict';
    case LeastRecentlyPlayed = 'least_recently_played';
    case Unfiltered = 'unfiltered';

    /**
     * @param string[] $logMessages
     */
    public static function fromLogMessages(array $logMessages): self
    {
        if (
            in_array(
                'No way to avoid same title OR same artist; using least recently played song.',
                $logMessages,
                true
            )
        ) {
            return self::LeastRecentlyPlayed;
        }

        if (in_array('Found track that avoids duplicate title and artist.', $logMessages, true)) {
            return self::Strict;
        }

        return self::Unfiltered;
    }

    public function getDescription(): string
    {
        return match ($this) {
            self::Strict => 'avoided a duplicate title and artist, possibly after a queue reset',
            self::LeastRecentlyPlayed => 'no way to avoid a duplicate, so the least recently played track was used',
            self::Unfiltered => 'no duplicate prevention, e.g. the playlist does not avoid duplicates',
        };
    }
}
