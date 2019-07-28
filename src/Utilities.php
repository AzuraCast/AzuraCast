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
     * Pull operating system details.
     * https://stackoverflow.com/questions/26862978/get-the-linux-distribution-name-in-php
     *
     * @return array
     */
    public static function getOperatingSystemDetails(): array
    {
        $vars = [];

        if (0 === stripos(PHP_OS, 'linux')) {
            $files = glob('/etc/*-release');

            foreach($files as $file)
            {
                $lines = array_filter(array_map(function($line) {
                    // split value from key
                    $parts = explode('=', $line);

                    // makes sure that "useless" lines are ignored (together with array_filter)
                    if (count($parts) !== 2) {
                        return false;
                    }

                    // remove quotes, if the value is quoted
                    $parts[1] = str_replace(array('"', "'"), '', $parts[1]);
                    return $parts;
                }, file($file)));

                foreach($lines as $line) {
                    $vars[$line[0]] = trim($line[1]);
                }
            }
        }

        return $vars;
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
