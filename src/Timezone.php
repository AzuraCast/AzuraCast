<?php
namespace App;

use DateTime;
use DateTimeZone;

class Timezone
{
    public static function l(DateTime $date_time)
    {
        return self::localize($date_time);
    }

    public static function localize(DateTime $date_time)
    {
        $tz_name = date_default_timezone_get();
        $tz = new DateTimeZone($tz_name);

        return $date_time->setTimezone($tz);
    }

    public static function getInfo(): array
    {
        static $tz_info;

        if (!$tz_info) {
            $tz = date_default_timezone_get();

            $utc = new DateTimeZone('UTC');
            $dt = new DateTime('now', $utc);

            $current_tz = new DateTimeZone($tz);
            $offset = $current_tz->getOffset($dt);

            $transition = $current_tz->getTransitions($dt->getTimestamp(), $dt->getTimestamp());

            $dt_in_tz = new DateTime('now', $current_tz);

            $tz_info = [
                'code' => $tz,
                'gmt_offset_seconds' => (float)$offset,
                'gmt_offset_hours' => (float)($offset / 3600),
                'name' => $transition[0]['name'],
                'abbr' => $transition[0]['abbr'],
                'tz_object' => $current_tz,
                'utc_object' => $utc,
                'now_utc' => $dt,
                'now' => $dt_in_tz,
            ];
        }

        return $tz_info;
    }

    public static function getOffsetMinutes($tz = null): int
    {
        if ($tz === null) {
            $tz = date_default_timezone_get();
        }

        $utc = new DateTimeZone('UTC');
        $dt = new DateTime('now', $utc);

        $current_tz = new DateTimeZone($tz);
        $offset = $current_tz->getOffset($dt);

        return (int)($offset / 60);
    }

    public static function formatOffset($offset): string
    {
        $hours = $offset / 3600;
        $remainder = $offset % 3600;
        $sign = $hours > 0 ? '+' : '-';
        $hour = (int)abs($hours);
        $minutes = (int)abs($remainder / 60);

        if ($hour === 0 && $minutes === 0) {
            return 'UTC';
        }

        return 'UTC ' . $sign . str_pad($hour, 2, '0', STR_PAD_LEFT) . ':' . str_pad($minutes, 2, '0');
    }

    public static function fetchSelect()
    {
        static $tz_select;

        if (!$tz_select) {
            $tz_select = [
                'UTC' => [
                    'UTC' => 'UTC',
                ],
            ];
            foreach (DateTimeZone::listIdentifiers((DateTimeZone::ALL ^ DateTimeZone::ANTARCTICA ^ DateTimeZone::UTC)) as $tz_identifier) {
                $tz = new DateTimeZone($tz_identifier);
                $tz_region = substr($tz_identifier, 0, strpos($tz_identifier, '/')) ?: $tz_identifier;
                $tz_subregion = str_replace([$tz_region . '/', '_'], ['', ' '], $tz_identifier) ?: $tz_region;

                $offset = $tz->getOffset(new DateTime);

                $offset_prefix = $offset < 0 ? '-' : '+';
                $offset_formatted = gmdate(($offset % 60 === 0) ? 'G' : 'G:i', abs($offset));

                $pretty_offset = ($offset === 0) ? 'UTC' : 'UTC' . $offset_prefix . $offset_formatted;

                if ($tz_subregion != $tz_region) {
                    $tz_subregion .= ' (' . $pretty_offset . ')';
                }

                $tz_select[$tz_region][$tz_identifier] = $tz_subregion;
            }
        }

        return $tz_select;
    }
}
