<?php
/**
 * Miscellaneous Utilities Class
 **/

namespace App;

class Utilities
{
    /**
     * Pretty print_r
     *
     * @param $var
     * @param bool $return
     * @return string
     */
    public static function print_r($var, $return = FALSE)
    {
        $return_value = '<pre style="font-size: 13px; font-family: Consolas, Courier New, Courier, monospace; color: #000; background: #EFEFEF; border: 1px solid #CCC; padding: 5px;">';
        $return_value .= print_r($var, TRUE);
        $return_value .= '</pre>';
        
        if ($return)
        {
            return $return_value;
        }
        else
        {
            echo $return_value;
        }
    }

    /**
     * Replacement for money_format that places the negative sign ahead of the dollar sign.
     *
     * @param $number
     * @return string
     */
    public static function money_format($number)
    {
        return (($number < 0) ? '-' : '') . money_format(abs($number), 2);
    }

    /**
     * Generate a randomized password of specified length.
     *
     * @param $char_length
     * @return string
     */
    public static function generatePassword($char_length = 8)
    {
        // String of all possible characters. Avoids using certain letters and numbers that closely resemble others.
        $numeric_chars = str_split('234679');
        $uppercase_chars = str_split('ACDEFGHJKLMNPQRTWXYZ');
        $lowercase_chars = str_split('acdefghjkmnpqrtwxyz');
        
        $chars = array($numeric_chars, $uppercase_chars, $lowercase_chars);
        
        $password = '';
        for($i = 1; $i <= $char_length; $i++)
        {
            $char_array = $chars[$i % 3];
            $password .= $char_array[mt_rand(0, count($char_array)-1)];
        }
        
        return str_shuffle($password);
    }

    /**
     * Convert a specified number of seconds into a date range.
     *
     * @param $timestamp
     * @return string
     */
    public static function timeToText($timestamp)
    {
        return self::timeDifferenceText(0, $timestamp);
    }

    /**
     * Get the textual difference between two strings.
     *
     * @param $timestamp1
     * @param $timestamp2
     * @param int $precision
     * @return string
     */
    public static function timeDifferenceText($timestamp1, $timestamp2, $precision = 1)
    {
        $time_diff = abs($timestamp1 - $timestamp2);
        
        if ($time_diff < 60)
        {
            $time_num = intval($time_diff);
            return sprintf(ngettext("%d second", "%d seconds", $time_num), $time_num);
        }
        else if ($time_diff >= 60 && $time_diff < 3600)
        {
            $time_num = round($time_diff / 60, $precision);
            return sprintf(ngettext("%d minute", "%d minutes", $time_num), $time_num);
        }
        else if ($time_diff >= 3600 && $time_diff < 216000)
        {
            $time_num = round($time_diff / 3600, $precision);
            return sprintf(ngettext("%d hour", "%d hours", $time_num), $time_num);
        }
        else if ($time_diff >= 216000 && $time_diff < 10368000)
        {
            $time_num = round($time_diff / 86400);
            return sprintf(ngettext("%d day", "%d days", $time_num), $time_num);
        }
        else
        {
            $time_num = round($time_diff / 2592000);
            return sprintf(ngettext("%d month", "%d months", $time_num), $time_num);
        }
    }

    /**
     * Forced-GMT strtotime alternative.
     *
     * @param $time
     * @param null $now
     * @return int
     */
    public static function gstrtotime($time, $now = NULL)
    {
        $prev_timezone = @date_default_timezone_get();
        @date_default_timezone_set('UTC');

        $timestamp = strtotime($time, $now);

        @date_default_timezone_set($prev_timezone);
        return $timestamp;
    }

    /**
     * Truncate text (adding "..." if needed)
     *
     * @param $text
     * @param int $limit
     * @param string $pad
     * @return string
     */
    public static function truncate_text($text, $limit = 80, $pad = '...')
    {
        mb_internal_encoding('UTF-8');

        if (mb_strlen($text) <= $limit)
        {
            return $text;
        }
        else
        {
            $wrapped_text = self::mb_wordwrap($text, $limit, "{N}", TRUE);
            $shortened_text = mb_substr($wrapped_text, 0, strpos($wrapped_text, "{N}"));
            
            // Prevent the padding string from bumping up against punctuation.
            $punctuation = array('.',',',';','?','!');
            if (in_array(mb_substr($shortened_text, -1), $punctuation))
            {
                $shortened_text = mb_substr($shortened_text, 0, -1);
            }
            
            return $shortened_text.$pad;
        }
    }

    /**
     * UTF-8 capable replacement for wordwrap function.
     *
     * @param $str
     * @param int $width
     * @param string $break
     * @param bool $cut
     * @return string
     */
    public static function mb_wordwrap($str, $width = 75, $break = "\n", $cut = false)
    {
        $lines = explode($break, $str);
        foreach ($lines as &$line) {
            $line = rtrim($line);
            if (mb_strlen($line) <= $width)
                continue;
            $words = explode(' ', $line);
            $line = '';
            $actual = '';
            foreach ($words as $word) {
                if (mb_strlen($actual.$word) <= $width)
                    $actual .= $word.' ';
                else {
                    if ($actual != '')
                        $line .= rtrim($actual).$break;
                    $actual = $word;
                    if ($cut) {
                        while (mb_strlen($actual) > $width) {
                            $line .= mb_substr($actual, 0, $width).$break;
                            $actual = mb_substr($actual, $width);
                        }
                    }
                    $actual .= ' ';
                }
            }
            $line .= trim($actual);
        }
        return implode($break, $lines);
    }

    /**
     * Truncate URL in text-presentable format (i.e. "http://www.example.com" becomes "example.com")
     *
     * @param $url
     * @param int $length
     * @return string
     */
    public static function truncate_url($url, $length=40)
    {
        $url = str_replace(array('http://', 'https://', 'www.'), array('', '', ''), $url);
        return self::truncate_text(rtrim($url, '/'), $length);
    }

    /**
     * Join one or more items into an array.
     *
     * @param array $items
     * @return string
     */
    public static function join_compound(array $items)
    {
        $count = count($items);

        if ($count == 0)
            return '';

        if ($count == 1)
            return $items[0];

        return implode(', ', array_slice($items, 0, -1)) . ' and ' . end($items);
    }

    /**
     * Create an array where the keys and values match each other.
     *
     * @param $array
     * @return array
     */
    public static function pairs($array)
    {
        return array_combine($array, $array);
    }

    /**
     * Split an array into "columns", typically for display purposes.
     *
     * @param $array
     * @param int $num_cols
     * @param bool $preserve_keys
     * @return array
     */
    public static function columns($array, $num_cols = 2, $preserve_keys = true)
    {
        $items_total = (int)count($array);
        $items_per_col = ceil($items_total / $num_cols);
        return array_chunk($array, $items_per_col, $preserve_keys);
    }

    /**
     * Split an array into "rows", typically for display purposes.
     *
     * @param $array
     * @param int $num_per_row
     * @param bool $preserve_keys
     * @return array
     */
    public static function rows($array, $num_per_row = 3, $preserve_keys = true)
    {
        return array_chunk($array, $num_per_row, $preserve_keys);
    }

    /**
     * array_merge_recursive does indeed merge arrays, but it converts values with duplicate
     * keys to arrays rather than overwriting the value in the first array with the duplicate
     * value in the second array, as array_merge does. I.e., with array_merge_recursive,
     * this happens (documented behavior):
     *
     * array_merge_recursive(array('key' => 'org value'), array('key' => 'new value'));
     *     => array('key' => array('org value', 'new value'));
     *
     * array_merge_recursive_distinct does not change the datatypes of the values in the arrays.
     * Matching keys' values in the second array overwrite those in the first array, as is the
     * case with array_merge, i.e.:
     *
     * array_merge_recursive_distinct(array('key' => 'org value'), array('key' => 'new value'));
     *     => array('key' => array('new value'));
     *
     * Parameters are passed by reference, though only for performance reasons. They're not
     * altered by this function.
     *
     * @param array $array1
     * @param array $array2
     * @return array
     * @author Daniel <daniel (at) danielsmedegaardbuus (dot) dk>
     * @author Gabriel Sobrinho <gabriel (dot) sobrinho (at) gmail (dot) com>
     */
    public static function array_merge_recursive_distinct(array &$array1, array &$array2)
    {
        $merged = $array1;
        foreach ($array2 as $key => &$value)
        {
            if (is_array($value) && isset($merged[$key]) && is_array($merged[$key]))
                $merged[$key] = self::array_merge_recursive_distinct($merged[$key], $value);
            else
                $merged[$key] = $value;
        }

        return $merged;
    }

    /**
     * Return all keys in a multi-dimensional array.
     * Useful for getting all possible values in an optgroup-stacked select dropdown.
     *
     * @param $array
     * @return array The keys found.
     */
    public static function array_keys_recursive($array)
    {
        $keys = array();

        foreach((array)$array as $key => $value)
        {
            if (is_array($value))
                $keys = array_merge($keys, self::array_keys_recursive($value));
            else
                $keys[] = $key;
        }

        return $keys;
    }

    /**
     * Sort a supplied array (the first argument) by one or more indices, specified in this format:
     * arrayOrderBy($data, [ 'index_name', SORT_ASC, 'index2_name', SORT_DESC ])
     *
     * Internally uses array_multisort().
     *
     * @param $data
     * @param array $args
     * @return mixed
     */
    public static function array_order_by($data, array $args = array())
    {
        if (empty($args))
            return $data;

        foreach ($args as $n => $field)
        {
            if (is_string($field))
            {
                $tmp = array();
                foreach ($data as $key => $row)
                    $tmp[$key] = $row[$field];
                $args[$n] = $tmp;
            }
        }

        $args[] = &$data;
        call_user_func_array('array_multisort', $args);
        return array_pop($args);
    }

    /**
     * Split a URL into an array (similar to parse_url() itself) but with cleaner parameter handling.
     *
     * @param $url
     * @return mixed
     */
    public static function parse_url($url)
    {
        $url_parts = @parse_url($url);
        $url_parts['path_clean'] = trim($url_parts['path'], '/');
        $url_parts['query_arr'] = self::convert_url_query($url_parts['query']);

        return $url_parts;
    }

    /**
     * Convert the query string of a URL into an array of keys and values.
     *
     * @param $query
     * @return array
     */
    public static function convert_url_query($query)
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
     * Construct a URL based on an array returned from parseUrl().
     *
     * @param $url
     * @return string
     */
    public static function build_url($url)
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
    
    /**
     * Construct a URL based on an array returned from parseUrl().
     *
     * @param $needle       The value we're looking for
     * @param $haystack     The array we're looking through
     * @param $strict       If true, checks type as well   
     * @return string
     */
    public static function recursive_array_search($needle, $haystack, $strict = false)
    {
        foreach($haystack as $key => $value) {
            if (is_array($value)) {
                // Value is an array, check that instead!
                $nextKey = self::recursive_array_search($needle, $value, $strict);
                
                if ($nextKey)
                    return $nextKey;
            }
            else if($strict ? $value === $needle : $value == $needle)
                return $key;
        }
        
        return false;
    }

    /**
     * Detect if the User-Agent matches common crawler UAs.
     * Not expected to be 100% accurate or trustworthy, just used to prevent
     * common crawlers from accessing features like API endpoints.
     *
     * @return bool
     */
    public static function is_crawler()
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
     * Get the system time zone.
     * @return string
     */
    public static function get_system_time_zone()
    {
        if (file_exists('/etc/timezone'))
        {
            // Ubuntu / Debian.
            $data = file_get_contents('/etc/timezone');
            if ($data)
                return trim($data);
        }
        elseif (is_link('/etc/localtime'))
        {
            // Mac OS X (and older Linuxes)
            // /etc/localtime is a symlink to the
            // timezone in /usr/share/zoneinfo.
            $filename = readlink('/etc/localtime');
            if (strpos($filename, '/usr/share/zoneinfo/') === 0)
                return substr($filename, 20);
        }
        elseif (file_exists('/etc/sysconfig/clock'))
        {
            // RHEL / CentOS
            $data = parse_ini_file('/etc/sysconfig/clock');
            if (!empty($data['ZONE']))
                return trim($data['ZONE']);
        }

        return 'UTC';
    }
}
