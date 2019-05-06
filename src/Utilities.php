<?php
/**
 * Miscellaneous Utilities Class
 **/

namespace App;

class Utilities
{
    /**
     * Generate a randomized password of specified length.
     *
     * @param int $char_length
     * @return string
     */
    public static function generatePassword($char_length = 8): string
    {
        // String of all possible characters. Avoids using certain letters and numbers that closely resemble others.
        $numeric_chars = str_split('234679');
        $uppercase_chars = str_split('ACDEFGHJKLMNPQRTWXYZ');
        $lowercase_chars = str_split('acdefghjkmnpqrtwxyz');

        $chars = [$numeric_chars, $uppercase_chars, $lowercase_chars];

        $password = '';
        for ($i = 1; $i <= $char_length; $i++) {
            $char_array = $chars[$i % 3];
            $password .= $char_array[mt_rand(0, count($char_array) - 1)];
        }

        return str_shuffle($password);
    }

    /**
     * Converts a time from seconds into its constituent time periods.
     *
     * @param int $timestamp
     * @return string The time displayed as years:months:days:hours:minutes:seconds
     */
    public static function timeToSplitDisplay(int $timestamp, $delimiter = ':'): string
    {
        $d1 = new \DateTime;
        $d2 = new \DateTime;
        $d2->add(new \DateInterval('PT'.$timestamp.'S'));

        $iv = $d2->diff($d1);
        $components = [
            $iv->y,
            $iv->m,
            $iv->d,
            $iv->h,
            $iv->i,
            $iv->s
        ];

        $show = false;
        $display = [];
        foreach($components as $k => $component) {
            if (0 !== $component || $show || $k >= 4) {
                if ($show && $k >= 4) {
                    $display[] = str_pad($component, 2, '0', \STR_PAD_LEFT);
                } else {
                    $display[] = $component;
                }

                $show = true;
            }
        }

        return implode($delimiter, $display);
    }

    /**
     * Convert a specified number of seconds into a date range.
     *
     * @param int $timestamp
     * @return string
     */
    public static function timeToText($timestamp): string
    {
        return self::timeDifferenceText(0, $timestamp);
    }

    /**
     * Get the textual difference between two strings.
     *
     * @param int $timestamp1
     * @param int $timestamp2
     * @param int $precision
     * @return string
     */
    public static function timeDifferenceText($timestamp1, $timestamp2, $precision = 1): string
    {
        $time_diff = abs($timestamp1 - $timestamp2);

        if ($time_diff < 60) {
            $time_num = (int)$time_diff;

            return sprintf(n__("%d second", "%d seconds", $time_num), $time_num);
        }
        if ($time_diff >= 60 && $time_diff < 3600) {
            $time_num = round($time_diff / 60, $precision);

            return sprintf(n__("%d minute", "%d minutes", $time_num), $time_num);
        }
        if ($time_diff >= 3600 && $time_diff < 216000) {
            $time_num = round($time_diff / 3600, $precision);

            return sprintf(n__("%d hour", "%d hours", $time_num), $time_num);
        }
        if ($time_diff >= 216000 && $time_diff < 10368000) {
            $time_num = round($time_diff / 86400);

            return sprintf(n__("%d day", "%d days", $time_num), $time_num);
        }

        $time_num = round($time_diff / 2592000);
        return sprintf(n__("%d month", "%d months", $time_num), $time_num);
    }

    /**
     * Truncate text (adding "..." if needed)
     *
     * @param string $text
     * @param int $limit
     * @param string $pad
     * @return string
     */
    public static function truncateText($text, $limit = 80, $pad = '...'): string
    {
        mb_internal_encoding('UTF-8');

        if (mb_strlen($text) <= $limit) {
            return $text;
        }

        $wrapped_text = self::mbWordwrap($text, $limit, '{N}', true);
        $shortened_text = mb_substr($wrapped_text, 0, strpos($wrapped_text, '{N}'));

        // Prevent the padding string from bumping up against punctuation.
        $punctuation = ['.', ',', ';', '?', '!'];
        if (in_array(mb_substr($shortened_text, -1), $punctuation, true)) {
            $shortened_text = mb_substr($shortened_text, 0, -1);
        }

        return $shortened_text . $pad;
    }

    /**
     * UTF-8 capable replacement for wordwrap function.
     *
     * @param string $str
     * @param int $width
     * @param string $break
     * @param bool $cut
     * @return string
     */
    public static function mbWordwrap($str, $width = 75, $break = "\n", $cut = false): string
    {
        $lines = explode($break, $str);
        foreach ($lines as &$line) {
            $line = rtrim($line);
            if (mb_strlen($line) <= $width) {
                continue;
            }
            $words = explode(' ', $line);
            $line = '';
            $actual = '';
            foreach ($words as $word) {
                if (mb_strlen($actual . $word) <= $width) {
                    $actual .= $word . ' ';
                } else {
                    if ($actual != '') {
                        $line .= rtrim($actual) . $break;
                    }
                    $actual = $word;
                    if ($cut) {
                        while (mb_strlen($actual) > $width) {
                            $line .= mb_substr($actual, 0, $width) . $break;
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
     * @param string $url
     * @param int $length
     * @return string
     */
    public static function truncateUrl($url, $length = 40): string
    {
        $url = str_replace(['http://', 'https://', 'www.'], '', $url);

        return self::truncateText(rtrim($url, '/'), $length);
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
    public static function arrayMergeRecursiveDistinct(array &$array1, array &$array2): array
    {
        $merged = $array1;
        foreach ($array2 as $key => &$value) {
            if (is_array($value) && isset($merged[$key]) && is_array($merged[$key])) {
                $merged[$key] = self::arrayMergeRecursiveDistinct($merged[$key], $value);
            } else {
                $merged[$key] = $value;
            }
        }

        return $merged;
    }

    /**
     * Sort a supplied array (the first argument) by one or more indices, specified in this format:
     * arrayOrderBy($data, [ 'index_name', SORT_ASC, 'index2_name', SORT_DESC ])
     *
     * Internally uses array_multisort().
     *
     * @param array $data
     * @param array $args
     * @return mixed
     */
    public static function arrayOrderBy($data, array $args = [])
    {
        if (empty($args)) {
            return $data;
        }

        foreach ($args as $n => $field) {
            if (is_string($field)) {
                $tmp = [];
                foreach ($data as $key => $row) {
                    $tmp[$key] = $row[$field];
                }
                $args[$n] = $tmp;
            }
        }

        $args[] = &$data;
        array_multisort(...$args);

        return array_pop($args);
    }

    /**
     * Detect if the User-Agent matches common crawler UAs.
     * Not expected to be 100% accurate or trustworthy, just used to prevent
     * common crawlers from accessing features like API endpoints.
     *
     * @return bool
     */
    public static function isCrawler(): bool
    {
        $ua = strtolower($_SERVER['HTTP_USER_AGENT']);

        $crawlers_agents = strtolower('Bloglines subscriber|Dumbot|Sosoimagespider|QihooBot|FAST-WebCrawler|Superdownloads Spiderman|LinkWalker|msnbot|ASPSeek|WebAlta Crawler|Lycos|FeedFetcher-Google|Yahoo|YoudaoBot|AdsBot-Google|Googlebot|Scooter|Gigabot|Charlotte|eStyle|AcioRobot|GeonaBot|msnbot-media|Baidu|CocoCrawler|Google|Charlotte t|Yahoo! Slurp China|Sogou web spider|YodaoBot|MSRBOT|AbachoBOT|Sogou head spider|AltaVista|IDBot|Sosospider|Yahoo! Slurp|Java VM|DotBot|LiteFinder|Yeti|Rambler|Scrubby|Baiduspider|accoona');
        $crawlers = explode('|', $crawlers_agents);

        foreach ($crawlers as $crawler) {
            if (strpos($ua, trim($crawler)) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get the system time zone.
     * @return string
     */
    public static function getSystemTimeZone(): string
    {
        if (APP_INSIDE_DOCKER) {
            return 'UTC';
        }

        if (file_exists('/etc/timezone')) {
            // Ubuntu / Debian.
            $data = file_get_contents('/etc/timezone');
            $data = trim($data);

            if (!empty($data)) {
                return $data;
            }
        } elseif (is_link('/etc/localtime')) {
            // Mac OS X (and older Linuxes)
            // /etc/localtime is a symlink to the
            // timezone in /usr/share/zoneinfo.
            $filename = readlink('/etc/localtime');
            if (strpos($filename, '/usr/share/zoneinfo/') === 0) {
                return substr($filename, 20);
            }
        } elseif (file_exists('/etc/sysconfig/clock')) {
            // RHEL / CentOS
            $data = parse_ini_file('/etc/sysconfig/clock');
            if (!empty($data['ZONE'])) {
                return trim($data['ZONE']);
            }
        }

        return 'UTC';
    }

    /**
     * Recursively remove a directory and its contents.
     *
     * @param string $dir
     */
    public static function rmdirRecursive($dir): void
    {
        if (is_dir($dir)) {
            $files = array_diff(scandir($dir, \SCANDIR_SORT_NONE), ['.', '..']);
            foreach ($files as $file) {
                self::rmdirRecursive($dir . '/' . $file);
            }

            @rmdir($dir);
        } else {
            @unlink($dir);
        }
    }

    /**
     * Attempt to fetch the most likely "external" IP for this instance.
     *
     * @return false|string
     */
    public static function getPublicIp()
    {
        if (APP_INSIDE_DOCKER) {
            if (APP_IN_PRODUCTION) {
                $public_ip = @file_get_contents('http://ipecho.net/plain');
                if (!empty($public_ip)) {
                    return $public_ip;
                }
            }

            return 'localhost';
        }

        return gethostbyname(gethostname()) ?? 'localhost';
    }

    /**
     * Flatten an array from format:
     * [
     *   'user' => [
     *     'id' => 1,
     *     'name' => 'test',
     *   ]
     * ]
     *
     * to format:
     * [
     *   'user.id' => 1,
     *   'user.name' => 'test',
     * ]
     *
     * This function is used to create replacements for variables in strings.
     *
     * @param array|object $array
     * @param string $separator
     * @param null $prefix
     * @return array
     */
    public static function flattenArray($array, $separator = '.', $prefix = null): array
    {
        if (!is_array($array)) {
            if (is_object($array)) {
                // Quick and dirty conversion from object to array.
                $array = json_decode(json_encode($array), true);
            } else {
                return $array;
            }
        }

        $return = [];

        foreach($array as $key => $value) {
            $return_key = $prefix ? $prefix.$separator.$key : $key;
            if (\is_array($value)) {
                $return = array_merge($return, self::flattenArray($value, $separator, $return_key));
            } else {
                $return[$return_key] = $value;
            }
        }

        return $return;
    }
}
