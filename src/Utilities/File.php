<?php

declare(strict_types=1);

namespace App\Utilities;

use InvalidArgumentException;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

use function stripos;

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
        $pattern = '/:\/\//';
        $path = preg_replace($pattern, '', $path) ?? $path;

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
    public static function sanitizeFileName(string $str): string
    {
        $str = mb_ereg_replace("([^\w\s\d\-_~,;\[\]\(\).])", '', $str);
        if (null === $str || false === $str) {
            throw new \RuntimeException('Cannot parse input string.');
        }

        $str = mb_ereg_replace("([\.]{2,})", '.', $str);
        if (null === $str || false === $str) {
            throw new \RuntimeException('Cannot parse input string.');
        }

        $str = str_replace(' ', '_', $str);
        return mb_strtolower($str) ?? '';
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
            throw new InvalidArgumentException(sprintf('Invalid path: "%s"', $path));
        }

        if (!str_starts_with($fullPath, $tempDir)) {
            throw new InvalidArgumentException(
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
            $realPath = $fileinfo->getRealPath();
            if (false === $realPath) {
                return false;
            }

            if ('link' !== $fileinfo->getType() && $fileinfo->isDir()) {
                if (!rmdir($realPath)) {
                    return false;
                }
            } elseif (!unlink($realPath)) {
                return false;
            }
        }

        return rmdir($source);
    }

    public static function renameDirectoryInPath(string $path, string $fromDir, string $toDir): string
    {
        if ('' === $fromDir && '' !== $toDir) {
            // Just prepend the new directory.
            return $toDir . '/' . $path;
        }

        if (0 === stripos($path, $fromDir)) {
            $newBasePath = ltrim(substr($path, strlen($fromDir)), '/');
            if ('' !== $toDir) {
                return $toDir . '/' . $newBasePath;
            }
            return $newBasePath;
        }

        return $path;
    }
}
