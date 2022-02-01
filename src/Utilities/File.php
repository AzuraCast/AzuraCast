<?php

declare(strict_types=1);

namespace App\Utilities;

use FilesystemIterator;
use InvalidArgumentException;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use SplFileInfo;
use Symfony\Component\Filesystem\Path;

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

    public static function generateTempPath(string $pattern = ''): string
    {
        $prefix = Path::getFilenameWithoutExtension($pattern) ?: 'temp';
        $extension = Path::getExtension($pattern) ?: 'log';

        return self::createTempFile(
            prefix: $prefix . '_',
            suffix: '.' . $extension
        );
    }

    public static function createTempFile(
        string $prefix = 'tmp_',
        string $suffix = '.tmp',
        string $dir = null
    ): string {
        $dir ??= sys_get_temp_dir();

        $tries = 1;
        while ($tries <= 5) {
            $rand = substr(uniqid('', true), -5);
            $path = $prefix . $rand . $suffix;

            $fullPath = Path::makeAbsolute($path, $dir);
            if (!is_file($fullPath)) {
                touch($fullPath);
                return $fullPath;
            }

            $tries++;
        }

        throw new \RuntimeException('Could not generate temp path.');
    }

    public static function validateTempPath(string $path): string
    {
        $tempDir = sys_get_temp_dir();
        $fullPath = Path::makeAbsolute($tempDir, $path);

        if (!Path::isBasePath($tempDir, $fullPath)) {
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
            new RecursiveDirectoryIterator($source, FilesystemIterator::SKIP_DOTS),
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
