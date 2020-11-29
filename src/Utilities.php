<?php

/**
 * Miscellaneous Utilities Class
 **/

namespace App;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

use function is_array;

class Utilities
{
    /**
     * Generate a randomized password of specified length.
     *
     * @param int $char_length
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
            $password .= $char_array[random_int(0, count($char_array) - 1)];
        }

        return str_shuffle($password);
    }

    /**
     * Truncate URL in text-presentable format (i.e. "http://www.example.com" becomes "example.com")
     *
     * @param string $url
     * @param int $length
     */
    public static function truncateUrl($url, $length = 40): string
    {
        $url = str_replace(['http://', 'https://', 'www.'], '', $url);

        return self::truncateText(rtrim($url, '/'), $length);
    }

    /**
     * Truncate text (adding "..." if needed)
     *
     * @param string $text
     * @param int $limit
     * @param string $pad
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
     * Sort a supplied array (the first argument) by one or more indices, specified in this format:
     * arrayOrderBy($data, [ 'index_name', SORT_ASC, 'index2_name', SORT_DESC ])
     *
     * Internally uses array_multisort().
     *
     * @param array $data
     * @param array $args
     *
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
     */
    public static function isCrawler(): bool
    {
        $ua = strtolower($_SERVER['HTTP_USER_AGENT']);

        // phpcs:disable Generic.Files.LineLength
        $crawlers_agents = strtolower('Bloglines subscriber|Dumbot|Sosoimagespider|QihooBot|FAST-WebCrawler|Superdownloads Spiderman|LinkWalker|msnbot|ASPSeek|WebAlta Crawler|Lycos|FeedFetcher-Google|Yahoo|YoudaoBot|AdsBot-Google|Googlebot|Scooter|Gigabot|Charlotte|eStyle|AcioRobot|GeonaBot|msnbot-media|Baidu|CocoCrawler|Google|Charlotte t|Yahoo! Slurp China|Sogou web spider|YodaoBot|MSRBOT|AbachoBOT|Sogou head spider|AltaVista|IDBot|Sosospider|Yahoo! Slurp|Java VM|DotBot|LiteFinder|Yeti|Rambler|Scrubby|Baiduspider|accoona');
        // phpcs:enable
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
     * @param string $source
     */
    public static function rmdirRecursive(string $source): bool
    {
        if (empty($source) || !file_exists($source)) {
            return true;
        }

        if (is_file($source) || is_link($source)) {
            return @unlink($source);
        }

        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($source, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($files as $fileinfo) {
            /** @var SplFileInfo $fileinfo */
            if ('link' !== $fileinfo->getType() && $fileinfo->isDir()) {
                if (!rmdir($fileinfo->getRealPath())) {
                    return false;
                }
            } elseif (!unlink($fileinfo->getRealPath())) {
                return false;
            }
        }

        return rmdir($source);
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
     *
     * @return mixed[]
     */
    public static function flattenArray($array, $separator = '.', $prefix = null): array
    {
        if (!is_array($array)) {
            if (is_object($array)) {
                // Quick and dirty conversion from object to array.
                $array = json_decode(json_encode($array, JSON_THROW_ON_ERROR), true, 512, JSON_THROW_ON_ERROR);
            } else {
                return $array;
            }
        }

        $return = [];

        foreach ($array as $key => $value) {
            $return_key = $prefix ? $prefix . $separator . $key : $key;
            if (is_array($value)) {
                $return = array_merge($return, self::flattenArray($value, $separator, $return_key));
            } else {
                $return[$return_key] = $value;
            }
        }

        return $return;
    }
}
