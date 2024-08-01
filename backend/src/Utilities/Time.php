<?php

declare(strict_types=1);

namespace App\Utilities;

final class Time
{
    public static function displayTimeToSeconds(string|float|int $seconds = null): ?float
    {
        if (null === $seconds || '' === $seconds) {
            return null;
        }
        if (is_float($seconds)) {
            return $seconds;
        }
        if (is_int($seconds)) {
            return (float)$seconds;
        }

        if (str_contains($seconds, ':')) {
            $sec = 0;
            foreach (array_reverse(explode(':', $seconds)) as $k => $v) {
                $sec += (60 ** (int)$k) * (int)$v;
            }

            return $sec;
        }

        return (float)$seconds;
    }
}
