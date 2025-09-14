<?php

declare(strict_types=1);

namespace App\Utilities;

use InvalidArgumentException;
use Normalizer;
use RuntimeException;

final class Strings
{
    /**
     * Truncate text (adding "..." if needed)
     */
    public static function truncateText(string $text, int $limit = 80, string $pad = '...'): string
    {
        if (mb_strlen($text) <= $limit) {
            return $text;
        }

        $wrappedText = self::mbWordwrap($text, $limit, '{N}', true);
        $shortenedText = mb_substr(
            $wrappedText,
            0,
            strpos($wrappedText, '{N}') ?: null
        );

        // Prevent the padding string from bumping up against punctuation.
        $punctuation = ['.', ',', ';', '?', '!'];
        if (in_array(mb_substr($shortenedText, -1), $punctuation, true)) {
            $shortenedText = mb_substr($shortenedText, 0, -1);
        }

        return $shortenedText . $pad;
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
            /** @noinspection RandomApiMigrationInspection */
            $str .= $keyspace[rand(0, $max)];
        }
        return $str;
    }

    /**
     * UTF-8 capable replacement for wordwrap function.
     *
     * @param string $str
     * @param int $width
     * @param non-empty-string $break
     * @param bool $cut
     */
    public static function mbWordwrap(string $str, int $width = 75, string $break = "\n", bool $cut = false): string
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
    public static function truncateUrl(?string $url, int $length = 40): string
    {
        if (null === $url) {
            return '';
        }

        /** @noinspection HttpUrlsUsage */
        $url = str_replace(['http://', 'https://', 'www.'], '', $url);

        return self::truncateText(rtrim($url, '/'), $length);
    }

    public static function getProgrammaticString(string $str): string
    {
        $result = mb_ereg_replace("([^\w\s\d\-_~,;\[\]\(\).])", '', $str);
        if (null === $result || false === $result) {
            throw new RuntimeException('Cannot parse input string.');
        }

        $result = mb_ereg_replace("([\.]{2,})", '.', $result);
        if (null === $result || false === $result) {
            throw new RuntimeException('Cannot parse input string.');
        }

        $result = str_replace(' ', '_', $result);
        return mb_strtolower($result);
    }

    protected static function fixWordQuotes(string $original): string
    {
        $quotes = [
            "\xC2\xAB" => '"', // « (U+00AB) in UTF-8
            "\xC2\xBB" => '"', // » (U+00BB) in UTF-8
            "\xE2\x80\x98" => "'", // ‘ (U+2018) in UTF-8
            "\xE2\x80\x99" => "'", // ’ (U+2019) in UTF-8
            "\xE2\x80\x9A" => "'", // ‚ (U+201A) in UTF-8
            "\xE2\x80\x9B" => "'", // ‛ (U+201B) in UTF-8
            "\xE2\x80\x9C" => '"', // “ (U+201C) in UTF-8
            "\xE2\x80\x9D" => '"', // ” (U+201D) in UTF-8
            "\xE2\x80\x9E" => '"', // „ (U+201E) in UTF-8
            "\xE2\x80\x9F" => '"', // ‟ (U+201F) in UTF-8
            "\xE2\x80\xB9" => "'", // ‹ (U+2039) in UTF-8
            "\xE2\x80\xBA" => "'", // › (U+203A) in UTF-8
        ];

        return strtr($original, $quotes);
    }

    public static function stringToUtf8(?string $original): string
    {
        $original ??= '';

        $encoding = mb_detect_encoding($original, ['auto']);
        if ($encoding !== false && $encoding !== 'UTF-8') {
            $original = mb_convert_encoding($original, 'UTF-8', $encoding);
            if ($original === false) {
                throw new RuntimeException('Cannot convert to UTF-8');
            }
        }

        if (!Normalizer::isNormalized($original)) {
            if (false === $original = Normalizer::normalize($original)) {
                throw new InvalidArgumentException('Invalid UTF-8 string.');
            }
        }

        return self::fixWordQuotes($original);
    }
}
