<?php
namespace PVL;

class Debug
{
	static $echo_debug = false;
	static $debug_log = array();
	static $timers = array();

	static function setEchoMode($new_value = true)
	{
		self::$echo_debug = $new_value;
	}

	// Logging
	static function log($entry)
	{
		if (self::$echo_debug)
		{
			if (DF_IS_COMMAND_LINE)
				echo "\n".$entry;
			else
				echo '<div>'.$entry.'</div>';
		}

		self::$debug_log[] = $entry;
	}

	static function print_r($item)
	{
		if (DF_IS_COMMAND_LINE)
		{
			$return_value = print_r($item, TRUE);
		}
		else
		{
			$return_value = '<pre style="font-size: 13px; font-family: Consolas, Courier New, Courier, monospace; color: #000; background: #EFEFEF; border: 1px solid #CCC; padding: 5px;">';
			$return_value .= print_r($item, TRUE);
			$return_value .= '</pre>';
		}

		self::log($return_value);
	}

	// Retrieval
	static function getLog()
	{
		return self::$debug_log;
	}

	static function printLog()
	{
		foreach(self::$debug_log as $log_row)
		{
			echo $log_row."\n";
		}
	}

	// Timers
	static function startTimer($timer_name)
	{
		self::$timers[$timer_name] = microtime(true);
	}
	static function endTimer($timer_name)
	{
		$start_time = (isset(self::$timers[$timer_name])) ? self::$timers[$timer_name] : microtime(true);
		$end_time = microtime(true);

		$time_diff = $end_time - $start_time;
		self::log('Timer "'.$timer_name.'" completed in '.round($time_diff, 3).' second(s).');

		unset(self::$timers[$timer_name]);
	}


}