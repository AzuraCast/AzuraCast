<?php

declare(strict_types=1);

namespace App\Utilities;

use Carbon\CarbonImmutable;
use DateTimeInterface;
use DateTimeZone;
use Doctrine\DBAL\Types\Exception\ValueNotConvertible;
use Exception;

final class Time
{
    public static function getUtc(): DateTimeZone
    {
        static $utc;

        if (!$utc) {
            $utc = new DateTimeZone('UTC');
        }

        return $utc;
    }

    public static function nowUtc(): CarbonImmutable
    {
        return CarbonImmutable::now(self::getUtc());
    }

    /**
     * Given either a Unix timestamp or an existing DateTime of any variety, returns
     * a CarbonImmutable instance set (or shifted) to UTC.
     *
     * @param mixed $input
     * @return CarbonImmutable
     * @throws ValueNotConvertible
     */
    public static function toUtcCarbonImmutable(
        mixed $input
    ): CarbonImmutable {
        if (is_numeric($input)) {
            return CarbonImmutable::createFromTimestampUTC($input);
        }

        $time = null;
        $error = null;
        $utc = Time::getUtc();

        if ($input instanceof CarbonImmutable) {
            $time = $input;
        } elseif ($input instanceof DateTimeInterface) {
            $time = CarbonImmutable::instance($input);
        } else {
            try {
                $time = CarbonImmutable::parse($input, $utc);
            } catch (Exception $exception) {
                $error = $exception;
            }
        }

        if (!$time) {
            throw ValueNotConvertible::new(
                $input,
                CarbonImmutable::class,
                'Y-m-d H:i:s.u or any format supported by CarbonImmutable::parse()',
                $error
            );
        }

        if (!$time->isUtc()) {
            $time = $time->shiftTimezone($utc);
        }

        return $time;
    }

    /**
     * @param mixed $input
     * @return CarbonImmutable|null
     * @throws ValueNotConvertible
     */
    public static function toNullableUtcCarbonImmutable(
        mixed $input
    ): ?CarbonImmutable {
        if (null === $input) {
            return null;
        }

        return self::toUtcCarbonImmutable($input);
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
