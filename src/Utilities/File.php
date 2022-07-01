<?php

declare(strict_types=1);

namespace App\Utilities;

use InvalidArgumentException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;

use function stripos;

/**
 * Static class that facilitates the uploading, reading and deletion of files in a controlled directory.
 */
final class File
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
        return Strings::getProgrammaticString($str);
    }

    public static function generateTempPath(string $pattern = ''): string
    {
        $prefix = Path::getFilenameWithoutExtension($pattern) ?: 'temp';
        $extension = Path::getExtension($pattern) ?: 'log';

        return (new Filesystem())->tempnam(
            sys_get_temp_dir(),
            $prefix . '_',
            '.' . $extension
        );
    }

    public static function validateTempPath(string $path): string
    {
        $tempDir = sys_get_temp_dir();
        $fullPath = Path::makeAbsolute($path, $tempDir);

        if (!Path::isBasePath($tempDir, $fullPath)) {
            throw new InvalidArgumentException(
                sprintf('Path "%s" is not within "%s".', $fullPath, $tempDir)
            );
        }

        return $fullPath;
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
