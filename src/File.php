<?php
namespace App;

/**
 * Static class that facilitates the uploading, reading and deletion of files in a controlled directory.
 */
class File
{
    /**
     * @param string $path
     *
     * @return string
     */
    public static function sanitizePathPrefix(string $path): string
    {
        $pattern = '/.*:\/\//i';

        $path = preg_replace($pattern, '', $path);

        if (preg_match($pattern, $path)) {
            return self::sanitizePathPrefix($path);
        }

        return $path;
    }

    /**
     * Sanitize a user-specified filename for storage.
     * Credit to: http://stackoverflow.com/a/19018736
     *
     * @param string $str
     *
     * @return string
     */
    public static function sanitizeFileName($str): string
    {
        $str = strip_tags($str);
        $str = preg_replace('/[\r\n\t ]+/', ' ', $str);
        $str = preg_replace('/[\"\*\/\:\<\>\?\'\|]+/', ' ', $str);
        $str = strtolower($str);
        $str = html_entity_decode($str, ENT_QUOTES, "utf-8");
        $str = htmlentities($str, ENT_QUOTES, "utf-8");
        $str = preg_replace("/(&)([a-z])([a-z]+;)/i", '$2', $str);
        $str = str_replace(' ', '_', $str);
        $str = rawurlencode($str);
        $str = str_replace('%', '-', $str);
        return $str;
    }
}
