<?php
namespace PVL;

use \Entity\Settings;
use \Entity\Station;

class Utilities extends \DF\Utilities
{
    public static function showSpecialEventsMode()
    {
        if (Settings::getSetting('special_event', 0) == 1)
        {
            return true;
        }
        elseif (Settings::getSetting('special_event_if_stream_active', 0) == 1)
        {
            $stream = Station::getRepository()->findOneBy(array('name' => 'PVL Special Events'));
            if ($stream instanceof Station)
                return $stream->isPlaying();
            else
                return false;
        }
        else
        {
            return false;
        }
    }

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

    public static function buildUrl($url)
    {
        is_array($url) || $url = parse_url($url);

        if (is_array($url['query']))
            $url['query'] = http_build_query($url['query']);

        if (isset($url['path']) && substr($url['path'], 0, 1) !== '/')
            $url['path'] = '/' . $url['path'];

        $parsed_string = '';
        if (isset($url['scheme']))
            $parsed_string .= $url['scheme'] . '://';

        if (isset($url['user']))
        {
            $parsed_string .= $url['user'];

            if (isset($url['pass']))
                $parsed_string .= ':' . $url['pass'];

            $parsed_string .= '@';
        }

        if (isset($url['host']))
            $parsed_string .= $url['host'];

        if (isset($url['port']))
            $parsed_string .= ':' . $url['port'];

        if (!empty($url['path']))
            $parsed_string .= $url['path'];
        else
            $parsed_string .= '/';

        if (isset($url['query']))
            $parsed_string .= '?' . $url['query'];

        if (isset($url['fragment']))
            $parsed_string .= '#' . $url['fragment'];

        return $parsed_string;
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

    /**
     * Array Sorting
     */

    public static function orderBy(&$ary, $clause, $ascending = true) 
    {
        $clause = str_ireplace('order by', '', $clause);
        $clause = preg_replace('/\s+/', ' ', $clause);
        $keys = explode(',', $clause);
        $dirMap = array('desc' => 1, 'asc' => -1);
        $def = $ascending ? -1 : 1;

        $keyAry = array();
        $dirAry = array();
        foreach($keys as $key) {
            $key = explode(' ', trim($key));
            $keyAry[] = trim($key[0]);
            if(isset($key[1])) {
                $dir = strtolower(trim($key[1]));
                $dirAry[] = $dirMap[$dir] ? $dirMap[$dir] : $def;
            } else {
                $dirAry[] = $def;
            }
        }

        $fnBody = '';
        for($i = count($keyAry) - 1; $i >= 0; $i--) {
            $k = $keyAry[$i];
            $t = $dirAry[$i];
            $f = -1 * $t;
            $aStr = '$a[\''.$k.'\']';
            $bStr = '$b[\''.$k.'\']';
            if(strpos($k, '(') !== false) {
                $aStr = '$a->'.$k;
                $bStr = '$b->'.$k;
            }

            if($fnBody == '') {
                $fnBody .= "if({$aStr} == {$bStr}) { return 0; }\n";
                $fnBody .= "return ({$aStr} < {$bStr}) ? {$t} : {$f};\n";               
            } else {
                $fnBody = "if({$aStr} == {$bStr}) {\n" . $fnBody;
                $fnBody .= "}\n";
                $fnBody .= "return ({$aStr} < {$bStr}) ? {$t} : {$f};\n";
            }
        }

        if($fnBody) {
            $sortFn = create_function('$a,$b', $fnBody);
            usort($ary, $sortFn);       
        }
    }
    
}