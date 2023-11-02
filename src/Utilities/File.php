<?php

declare(strict_types=1);

namespace App\Utilities;

use FilesystemIterator;
use InvalidArgumentException;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;

use function stripos;
use function strlen;

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

    /**
     * Clear all contents of a directory, without removing the directory itself.
     */
    public static function clearDirectoryContents(string $targetDir): void
    {
        $targetDir = rtrim($targetDir, '/\\');

        $flags = FilesystemIterator::SKIP_DOTS;
        $deleteIterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($targetDir, $flags),
            RecursiveIteratorIterator::CHILD_FIRST
        );

        $fsUtils = new Filesystem();

        /** @var SplFileInfo $file */
        foreach ($deleteIterator as $file) {
            $fsUtils->remove((string)$file);
        }
    }

    public static function moveDirectoryContents(
        string $originDir,
        string $targetDir,
        bool $clearDirectoryFirst = false
    ): void {
        if ($clearDirectoryFirst) {
            self::clearDirectoryContents($targetDir);
        }

        $targetDir = rtrim($targetDir, '/\\');
        $originDir = rtrim($originDir, '/\\');
        $originDirLen = strlen($originDir);

        $flags = FilesystemIterator::SKIP_DOTS;
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($originDir, $flags),
            RecursiveIteratorIterator::SELF_FIRST
        );

        $fsUtils = new Filesystem();
        $fsUtils->mkdir($targetDir);

        /** @var SplFileInfo $file */
        foreach ($iterator as $file) {
            if (
                $file->getPathname() === $targetDir
                || $file->getRealPath() === $targetDir
            ) {
                continue;
            }

            $target = $targetDir . substr($file->getPathname(), $originDirLen);

            if (is_link((string)$file)) {
                $fsUtils->symlink($file->getLinkTarget(), $target);
            } elseif (is_dir((string)$file)) {
                $fsUtils->mkdir($target);
            } elseif (is_file((string)$file)) {
                $fsUtils->rename((string)$file, $target, true);
            }
        }
    }

    public static function getFirstExistingFile(array $files): string
    {
        foreach ($files as $file) {
            if (file_exists($file)) {
                return $file;
            }
        }

        throw new InvalidArgumentException('No existing files found.');
    }

    public static function getFirstExistingDirectory(array $dirs): string
    {
        foreach ($dirs as $dir) {
            if (is_dir($dir)) {
                return $dir;
            }
        }

        throw new InvalidArgumentException('No existing directories found.');
    }
}
