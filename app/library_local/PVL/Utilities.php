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