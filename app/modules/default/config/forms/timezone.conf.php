<?php
// Time zone configuration
function formatOffset($offset)
{
    $hours = $offset / 3600;
    $remainder = $offset % 3600;
    $sign = $hours > 0 ? '+' : '-';
    $hour = (int) abs($hours);
    $minutes = (int) abs($remainder / 60);

    if ($hour == 0 && $minutes == 0)
        return 'GMT';
    else
        return 'GMT '. $sign . str_pad($hour, 2, '0', STR_PAD_LEFT) .':'. str_pad($minutes,2, '0');
}

$utc = new \DateTimeZone('UTC');
$dt = new \DateTime('now', $utc);

$current_time = time();

$tz_options = array(
    'US/Pacific'           => "Pacific Time (US & Canada)",
    'US/Mountain'          => "Mountain Time (US & Canada)",
    'US/Central'           => "Central Time (US & Canada)",
    'US/Eastern'           => "Eastern Time (US & Canada)",
    'Canada/Atlantic'      => "Atlantic Time (Canada)",
    'Pacific/Midway'       => "Midway Island",
    'US/Samoa'             => "Samoa",
    'US/Hawaii'            => "Hawaii",
    'US/Alaska'            => "Alaska",
    'America/Tijuana'      => "Tijuana",
    'US/Arizona'           => "Arizona",
    'America/Chihuahua'    => "Chihuahua",
    'America/Mazatlan'     => "Mazatlan",
    'America/Mexico_City'  => "Mexico City",
    'America/Monterrey'    => "Monterrey",
    'Canada/Saskatchewan'  => "Saskatchewan",
    'US/East-Indiana'      => "Indiana (East)",
    'America/Bogota'       => "Bogota",
    'America/Lima'         => "Lima",
    'America/Caracas'      => "Caracas",
    'America/La_Paz'       => "La Paz",
    'America/Santiago'     => "Santiago",
    'Canada/Newfoundland'  => "Newfoundland",
    'America/Buenos_Aires' => "Buenos Aires",
    'Atlantic/Stanley'     => "Stanley",
    'Atlantic/Azores'      => "Azores",
    'Atlantic/Cape_Verde'  => "Cape Verde Is.",
    'Africa/Casablanca'    => "Casablanca",
    'Europe/Dublin'        => "Dublin",
    'Europe/Lisbon'        => "Lisbon",
    'Europe/London'        => "London",
    'Africa/Monrovia'      => "Monrovia",
    'Europe/Amsterdam'     => "Amsterdam",
    'Europe/Belgrade'      => "Belgrade",
    'Europe/Berlin'        => "Berlin",
    'Europe/Bratislava'    => "Bratislava",
    'Europe/Brussels'      => "Brussels",
    'Europe/Budapest'      => "Budapest",
    'Europe/Copenhagen'    => "Copenhagen",
    'Europe/Ljubljana'     => "Ljubljana",
    'Europe/Madrid'        => "Madrid",
    'Europe/Paris'         => "Paris",
    'Europe/Prague'        => "Prague",
    'Europe/Rome'          => "Rome",
    'Europe/Sarajevo'      => "Sarajevo",
    'Europe/Skopje'        => "Skopje",
    'Europe/Stockholm'     => "Stockholm",
    'Europe/Vienna'        => "Vienna",
    'Europe/Warsaw'        => "Warsaw",
    'Europe/Zagreb'        => "Zagreb",
    'Europe/Athens'        => "Athens",
    'Europe/Bucharest'     => "Bucharest",
    'Africa/Cairo'         => "Cairo",
    'Africa/Harare'        => "Harare",
    'Europe/Helsinki'      => "Helsinki",
    'Europe/Istanbul'      => "Istanbul",
    'Asia/Jerusalem'       => "Jerusalem",
    'Europe/Kiev'          => "Kyiv",
    'Europe/Minsk'         => "Minsk",
    'Europe/Riga'          => "Riga",
    'Europe/Sofia'         => "Sofia",
    'Europe/Tallinn'       => "Tallinn",
    'Europe/Vilnius'       => "Vilnius",
    'Asia/Baghdad'         => "Baghdad",
    'Asia/Kuwait'          => "Kuwait",
    'Africa/Nairobi'       => "Nairobi",
    'Asia/Riyadh'          => "Riyadh",
    'Asia/Tehran'          => "Tehran",
    'Europe/Moscow'        => "Moscow",
    'Asia/Baku'            => "Baku",
    'Europe/Volgograd'     => "Volgograd",
    'Asia/Muscat'          => "Muscat",
    'Asia/Tbilisi'         => "Tbilisi",
    'Asia/Yerevan'         => "Yerevan",
    'Asia/Kabul'           => "Kabul",
    'Asia/Karachi'         => "Karachi",
    'Asia/Tashkent'        => "Tashkent",
    'Asia/Kolkata'         => "Kolkata",
    'Asia/Kathmandu'       => "Kathmandu",
    'Asia/Yekaterinburg'   => "Ekaterinburg",
    'Asia/Almaty'          => "Almaty",
    'Asia/Dhaka'           => "Dhaka",
    'Asia/Novosibirsk'     => "Novosibirsk",
    'Asia/Bangkok'         => "Bangkok",
    'Asia/Jakarta'         => "Jakarta",
    'Asia/Krasnoyarsk'     => "Krasnoyarsk",
    'Asia/Chongqing'       => "Chongqing",
    'Asia/Hong_Kong'       => "Hong Kong",
    'Asia/Kuala_Lumpur'    => "Kuala Lumpur",
    'Australia/Perth'      => "Perth",
    'Asia/Singapore'       => "Singapore",
    'Asia/Taipei'          => "Taipei",
    'Asia/Ulaanbaatar'     => "Ulaan Bataar",
    'Asia/Urumqi'          => "Urumqi",
    'Asia/Irkutsk'         => "Irkutsk",
    'Asia/Seoul'           => "Seoul",
    'Asia/Tokyo'           => "Tokyo",
    'Australia/Adelaide'   => "Adelaide",
    'Australia/Darwin'     => "Darwin",
    'Asia/Yakutsk'         => "Yakutsk",
    'Australia/Brisbane'   => "Brisbane",
    'Australia/Canberra'   => "Canberra",
    'Pacific/Guam'         => "Guam",
    'Australia/Hobart'     => "Hobart",
    'Australia/Melbourne'  => "Melbourne",
    'Pacific/Port_Moresby' => "Port Moresby",
    'Australia/Sydney'     => "Sydney",
    'Asia/Vladivostok'     => "Vladivostok",
    'Asia/Magadan'         => "Magadan",
    'Pacific/Auckland'     => "Auckland",
    'Pacific/Fiji'         => "Fiji",
);

$tz_select_raw = array();
foreach($tz_options as $tz => $tz_display)
{
    $current_tz = new \DateTimeZone($tz);
    $offset =  $current_tz->getOffset($dt);

    $tz_select_raw[$offset][$tz] = $tz_display;
}

ksort($tz_select_raw);

$tz_select = array();

foreach($tz_select_raw as $offset => $cities)
{
    $offset_name = formatOffset($offset);
    $offset_key = key($cities);
    $offset_cities = implode(', ', $cities);

    $tz_select[$offset_key] = '['.$offset_name.'] '.$offset_cities;
}

return array(
    'method'        => 'post',
    'elements'      => array(

        'timezone' => array('radio', array(
            'label' => 'Select New Timezone',
            'multiOptions' => $tz_select,
            'required' => true,
        )),

        'submit'        => array('submit', array(
            'type'  => 'submit',
            'label' => 'Save Changes',
            'helper' => 'formButton',
            'class' => 'ui-button',
        )),
    ),
);