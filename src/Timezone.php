<?php
namespace App;

class Timezone
{
    public static function l(\DateTime $date_time)
    {
        return self::localize($date_time);
    }

    public static function localize(\DateTime $date_time)
    {
        $tz_name = date_default_timezone_get();
        $tz = new \DateTimeZone($tz_name);

        return $date_time->setTimezone($tz);
    }

    public static function getInfo(): array
    {
        static $tz_info;

        if (!$tz_info) {
            $tz = date_default_timezone_get();

            $utc = new \DateTimeZone('UTC');
            $dt = new \DateTime('now', $utc);

            $current_tz = new \DateTimeZone($tz);
            $offset = $current_tz->getOffset($dt);

            $transition = $current_tz->getTransitions($dt->getTimestamp(), $dt->getTimestamp());

            $dt_in_tz = new \DateTime('now', $current_tz);

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

        $utc = new \DateTimeZone('UTC');
        $dt = new \DateTime('now', $utc);

        $current_tz = new \DateTimeZone($tz);
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
            return 'GMT';
        }

        return 'GMT ' . $sign . str_pad($hour, 2, '0', STR_PAD_LEFT) . ':' . str_pad($minutes, 2, '0');
    }

    public static function fetchSelect()
    {
        static $tz_select;

        if (!$tz_select) {
            $tz_options = [
                'Africa/Cairo' => 'Cairo',
                'Africa/Casablanca' => 'Casablanca',
                'Africa/Harare' => 'Harare',
                'Africa/Monrovia' => 'Monrovia',
                'Africa/Nairobi' => 'Nairobi',
                'America/Bogota' => 'Bogota',
                'America/Buenos_Aires' => 'Buenos Aires',
                'America/Caracas' => 'Caracas',
                'America/Chihuahua' => 'Chihuahua',
                'America/La_Paz' => 'La Paz',
                'America/Lima' => 'Lima',
                'America/Mazatlan' => 'Mazatlan',
                'America/Mexico_City' => 'Mexico City',
                'America/Monterrey' => 'Monterrey',
                'America/Santiago' => 'Santiago',
                'America/Tijuana' => 'Tijuana',
                'Asia/Almaty' => 'Almaty',
                'Asia/Baghdad' => 'Baghdad',
                'Asia/Baku' => 'Baku',
                'Asia/Bangkok' => 'Bangkok',
                'Asia/Chongqing' => 'Chongqing',
                'Asia/Dhaka' => 'Dhaka',
                'Asia/Hong_Kong' => 'Hong Kong',
                'Asia/Irkutsk' => 'Irkutsk',
                'Asia/Jakarta' => 'Jakarta',
                'Asia/Jerusalem' => 'Jerusalem',
                'Asia/Kabul' => 'Kabul',
                'Asia/Karachi' => 'Karachi',
                'Asia/Kathmandu' => 'Kathmandu',
                'Asia/Kolkata' => 'Kolkata',
                'Asia/Krasnoyarsk' => 'Krasnoyarsk',
                'Asia/Kuala_Lumpur' => 'Kuala Lumpur',
                'Asia/Kuwait' => 'Kuwait',
                'Asia/Magadan' => 'Magadan',
                'Asia/Muscat' => 'Muscat',
                'Asia/Novosibirsk' => 'Novosibirsk',
                'Asia/Riyadh' => 'Riyadh',
                'Asia/Seoul' => 'Seoul',
                'Asia/Singapore' => 'Singapore',
                'Asia/Taipei' => 'Taipei',
                'Asia/Tashkent' => 'Tashkent',
                'Asia/Tbilisi' => 'Tbilisi',
                'Asia/Tehran' => 'Tehran',
                'Asia/Tokyo' => 'Tokyo',
                'Asia/Ulaanbaatar' => 'Ulaan Bataar',
                'Asia/Urumqi' => 'Urumqi',
                'Asia/Vladivostok' => 'Vladivostok',
                'Asia/Yakutsk' => 'Yakutsk',
                'Asia/Yekaterinburg' => 'Ekaterinburg',
                'Asia/Yerevan' => 'Yerevan',
                'Atlantic/Azores' => 'Azores',
                'Atlantic/Cape_Verde' => 'Cape Verde Is.',
                'Atlantic/Stanley' => 'Stanley',
                'Australia/Adelaide' => 'Adelaide',
                'Australia/Brisbane' => 'Brisbane',
                'Australia/Canberra' => 'Canberra',
                'Australia/Darwin' => 'Darwin',
                'Australia/Hobart' => 'Hobart',
                'Australia/Melbourne' => 'Melbourne',
                'Australia/Perth' => 'Perth',
                'Australia/Sydney' => 'Sydney',
                'Canada/Atlantic' => 'Atlantic Time (Canada)',
                'Canada/Newfoundland' => 'Newfoundland',
                'Canada/Saskatchewan' => 'Saskatchewan',
                'Europe/Amsterdam' => 'Amsterdam',
                'Europe/Athens' => 'Athens',
                'Europe/Belgrade' => 'Belgrade',
                'Europe/Berlin' => 'Berlin',
                'Europe/Bratislava' => 'Bratislava',
                'Europe/Brussels' => 'Brussels',
                'Europe/Bucharest' => 'Bucharest',
                'Europe/Budapest' => 'Budapest',
                'Europe/Copenhagen' => 'Copenhagen',
                'Europe/Dublin' => 'Dublin',
                'Europe/Helsinki' => 'Helsinki',
                'Europe/Istanbul' => 'Istanbul',
                'Europe/Kiev' => 'Kyiv',
                'Europe/Lisbon' => 'Lisbon',
                'Europe/Ljubljana' => 'Ljubljana',
                'Europe/London' => 'London',
                'Europe/Madrid' => 'Madrid',
                'Europe/Minsk' => 'Minsk',
                'Europe/Moscow' => 'Moscow',
                'Europe/Paris' => 'Paris',
                'Europe/Prague' => 'Prague',
                'Europe/Riga' => 'Riga',
                'Europe/Rome' => 'Rome',
                'Europe/Sarajevo' => 'Sarajevo',
                'Europe/Skopje' => 'Skopje',
                'Europe/Sofia' => 'Sofia',
                'Europe/Stockholm' => 'Stockholm',
                'Europe/Tallinn' => 'Tallinn',
                'Europe/Vienna' => 'Vienna',
                'Europe/Vilnius' => 'Vilnius',
                'Europe/Volgograd' => 'Volgograd',
                'Europe/Warsaw' => 'Warsaw',
                'Europe/Zagreb' => 'Zagreb',
                'Pacific/Auckland' => 'Auckland',
                'Pacific/Fiji' => 'Fiji',
                'Pacific/Guam' => 'Guam',
                'Pacific/Midway' => 'Midway Island',
                'Pacific/Port_Moresby' => 'Port Moresby',
                'US/Alaska' => 'Alaska',
                'US/Arizona' => 'Arizona',
                'US/Central' => 'Central Time (US & Canada)',
                'US/East-Indiana' => 'Indiana (East)',
                'US/Eastern' => 'Eastern Time (US & Canada)',
                'US/Hawaii' => 'Hawaii',
                'US/Mountain' => 'Mountain Time (US & Canada)',
                'US/Pacific' => 'Pacific Time (US & Canada)',
                'US/Samoa' => 'Samoa',
            ];

            $tz_select = [
                'UTC' => [
                    'UTC' => 'UTC',
                ],
            ];

            foreach ($tz_options as $tz => $tz_display) {
                $tz_region = substr($tz, 0, strpos($tz, '/'));
                $tz_select[$tz_region][$tz] = $tz_display;
            }
        }

        return $tz_select;
    }
}
