<?php
namespace PVL\NowPlayingAdapter;

use \Entity\Station;

class AdapterAbstract
{
	protected $station;
	protected $url;

	public function __construct(Station $station)
	{
		$this->station = $station;
		$this->url = $station->nowplaying_url;
	}

	/* Process a nowplaying record. */
	public function process(&$np)
	{
		return $np;
	}

	/* Fetch a remote URL. */
	protected function getUrl($url = null, $cache_time = 0)
	{
		if ($url === null)
			$url = $this->url;

		$cache_name = 'nowplaying_url_'.substr(md5($url), 0, 10);
		if ($cache_time > 0)
		{
			$return_raw = \DF\Cache::load($cache_name);
			if ($return_raw)
				return $return_raw;
		}

		$curl_start = time();

		// Start cURL request.
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, $url);  
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true); 
		curl_setopt($curl, CURLOPT_TIMEOUT, 10); 
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($curl, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.2) Gecko/20070219 Firefox/2.0.0.2');  

		$return_raw = \PVL\Utilities::curl_exec_utf8($curl);
		// End cURL request.

		$curl_end = time();
		$curl_time = $curl_end - $curl_start;

		\PVL\Debug::log("Curl processed in ".$curl_time." second(s).");

		$error = curl_error($curl);
		if ($error)
			\PVL\Debug::log("Curl error: ".$error);

		if ($cache_time > 0)
			\DF\Cache::save($return_raw, $cache_name, array(), $cache_time);
		
		return trim($return_raw);
	}

	/* Calculate listener count from unique and current totals. */
	protected function getListenerCount($unique_listeners = 0, $current_listeners = 0)
    {
    	$unique_listeners = (int)$unique_listeners;
    	$current_listeners = (int)$current_listeners;

    	if ($unique_listeners == 0 || $current_listeners == 0)
    		return max($unique_listeners, $current_listeners);
    	else
    		return min($unique_listeners, $current_listeners);

    	// return round(($unique_listeners + $unique_listeners + $current_listeners) / 3);
    }

}