<?
namespace PVL;

class Utilities
{
	public static function parseUrl($url)
	{
		$url_parts = @parse_url($url);
		$url_parts['path_clean'] = trim($url_parts['path'], '/');
		$url_parts['query_arr'] = self::convertUrlQuery($url_parts['query']);

		return $url_parts;
	}

	public static function convertUrlQuery($query)
	{
		$queryParts = explode('&', $query); 
		$params = array(); 
		foreach ($queryParts as $param)
		{ 
			$item = explode('=', $param); 
			$params[$item[0]] = $item[1]; 
		} 
		return $params; 
	} 

	public static function curl_exec_utf8($ch)
	{
		$data = curl_exec($ch);
		if (!is_string($data)) return $data;

		unset($charset);
		$content_type = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);

		/* 1: HTTP Content-Type: header */
		preg_match( '@([\w/+]+)(;\s*charset=(\S+))?@i', $content_type, $matches );
		if ( isset( $matches[3] ) )
			$charset = $matches[3];

		/* 2: <meta> element in the page */
		if (!isset($charset)) {
			preg_match( '@<meta\s+http-equiv="Content-Type"\s+content="([\w/]+)(;\s*charset=([^\s"]+))?@i', $data, $matches );
			if ( isset( $matches[3] ) )
				$charset = $matches[3];
		}

		/* 3: <xml> element in the page */
		if (!isset($charset)) {
			preg_match( '@<\?xml.+encoding="([^\s"]+)@si', $data, $matches );
			if ( isset( $matches[1] ) )
				$charset = $matches[1];
		}

		/* 4: PHP's heuristic detection */
		if (!isset($charset)) {
			$encoding = mb_detect_encoding($data);
			if ($encoding)
				$charset = $encoding;
		}

		/* 5: Default for HTML */
		if (!isset($charset)) {
			if (strstr($content_type, "text/html") === 0)
				$charset = "ISO 8859-1";
		}

		/* Convert it if it is anything but UTF-8 */
		if (isset($charset) && strtoupper($charset) != "UTF-8")
			$data = iconv($charset, 'UTF-8//IGNORE', $data);

		return $data;
	}

	/**
	 * User-Agent Detection
	 */

	public static function isCrawler()
	{
		$ua = strtolower($_SERVER['HTTP_USER_AGENT']);

    	$crawlers_agents = strtolower('Bloglines subscriber|Dumbot|Sosoimagespider|QihooBot|FAST-WebCrawler|Superdownloads Spiderman|LinkWalker|msnbot|ASPSeek|WebAlta Crawler|Lycos|FeedFetcher-Google|Yahoo|YoudaoBot|AdsBot-Google|Googlebot|Scooter|Gigabot|Charlotte|eStyle|AcioRobot|GeonaBot|msnbot-media|Baidu|CocoCrawler|Google|Charlotte t|Yahoo! Slurp China|Sogou web spider|YodaoBot|MSRBOT|AbachoBOT|Sogou head spider|AltaVista|IDBot|Sosospider|Yahoo! Slurp|Java VM|DotBot|LiteFinder|Yeti|Rambler|Scrubby|Baiduspider|accoona');
    	$crawlers = explode("|", $crawlers_agents);

    	foreach($crawlers as $crawler)
        {
            if (strpos($ua, trim($crawler)) !== false)
                return true;
        }

    	return false;
	}
}