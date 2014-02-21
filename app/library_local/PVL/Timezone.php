<?php
namespace PVL;

class Timezone
{
	public static function l(\DateTime $date_time)
	{
		return self::localize($date_time);
	}
	public static function localize(\DateTime $date_time)
	{
		$tz_name = Customization::get('timezone');
		$tz = new \DateTimeZone($tz_name);

		return $date_time->setTimezone($tz);
	}

	public static function getInfo()
	{
		$tz = Customization::get('timezone');

		$utc = new \DateTimeZone('UTC');
		$dt = new \DateTime('now', $utc);

		$current_tz = new \DateTimeZone($tz);
		$offset =  $current_tz->getOffset($dt);

		$transition =  $current_tz->getTransitions($dt->getTimestamp(), $dt->getTimestamp());
		$abbr = $transition[0]['abbr'];

		$dt_in_tz = new \DateTime('now', $current_tz);

		return array(
			'name'			=> $tz['name'],
			'abbr'			=> $transition[0]['abbr'],
			'tz_object'		=> $current_tz,
			'utc_object'	=> $utc,
			'now_utc'		=> $dt,
			'now'			=> $dt_in_tz,
		);
	}
}