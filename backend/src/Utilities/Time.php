<?php

declare(strict_types=1);

namespace App\Utilities;

use Carbon\CarbonImmutable;
use Carbon\CarbonTimeZone;
use DateTimeZone;

final class Time
{
    public static function getUtc(): CarbonTimeZone
    {
        static $utc;

        if (!$utc) {
            $utc = CarbonTimeZone::create(new DateTimeZone('UTC'));
        }

        return $utc;
    }

    public static function nowUtc(): CarbonImmutable
    {
        return CarbonImmutable::now(self::getUtc());
    }

    public static function displayTimeToSeconds(string|float|int|null $seconds = null): ?float
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
