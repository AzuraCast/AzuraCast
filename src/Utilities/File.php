<?php

namespace App\Utilities;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

/**
 * Static class that facilitates the uploading, reading and deletion of files in a controlled directory.
 */
class File
{
    /**
     * @param string $path
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

    public static function generateTempPath(string $pattern = ''): string
    {
        $prefix = pathinfo($pattern, PATHINFO_FILENAME) ?? 'temp';
        $extension = pathinfo($pattern, PATHINFO_EXTENSION) ?? 'log';

        $tempPath = tempnam(sys_get_temp_dir(), $prefix . '_') . '.' . $extension;
        touch($tempPath);

        return $tempPath;
    }

    public static function validateTempPath(string $path): string
    {
        $tempDir = sys_get_temp_dir();
        $fullPath = realpath($tempDir . '/' . $path);

        if (false === $fullPath) {
            throw new \InvalidArgumentException(sprintf('Invalid path: "%s"', $path));
        }

        if (0 !== strpos($fullPath, $tempDir)) {
            throw new \InvalidArgumentException(
                sprintf('Path "%s" is not within "%s".', $fullPath, $tempDir)
            );
        }

        return $fullPath;
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
}
