<?php

declare(strict_types=1);

namespace App\Utilities;

class Strings
{
    /**
     * Truncate text (adding "..." if needed)
     *
     * @param string $text
     * @param int $limit
     * @param string $pad
     */
    public static function truncateText(string $text, $limit = 80, $pad = '...'): string
    {
        mb_internal_encoding('UTF-8');

        if (mb_strlen($text) <= $limit) {
            return $text;
        }

        $wrapped_text = self::mbWordwrap($text, $limit, '{N}', true);
        $shortened_text = mb_substr(
            $wrapped_text,
            0,
            strpos($wrapped_text, '{N}') ?: null
        );

        // Prevent the padding string from bumping up against punctuation.
        $punctuation = ['.', ',', ';', '?', '!'];
        if (in_array(mb_substr($shortened_text, -1), $punctuation, true)) {
            $shortened_text = mb_substr($shortened_text, 0, -1);
        }

        return $shortened_text . $pad;
    }

    /**
     * Generate a randomized password of specified length.
     *
     * @param int $length
     */
    public static function generatePassword(int $length = 8): string
    {
        // String of all possible characters. Avoids using certain letters and numbers that closely resemble others.
        $keyspace = '234679ACDEFGHJKLMNPQRTWXYZacdefghjkmnpqrtwxyz';

        $str = '';
        $max = mb_strlen($keyspace, '8bit') - 1;

        for ($i = 0; $i < $length; ++$i) {
            $str .= $keyspace[random_int(0, $max)];
        }
        return $str;
    }

    /**
     * UTF-8 capable replacement for wordwrap function.
     *
     * @param string $str
     * @param int $width
     * @param string $break
     * @param bool $cut
     */
    public static function mbWordwrap(string $str, $width = 75, $break = "\n", $cut = false): string
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
     * @param string|null $url
     * @param int $length
     */
    public static function truncateUrl(?string $url, $length = 40): string
    {
        if (null === $url) {
            return '';
        }

        /** @noinspection HttpUrlsUsage */
        $url = str_replace(['http://', 'https://', 'www.'], '', $url);

        return self::truncateText(rtrim($url, '/'), $length);
    }
}
