<?php

namespace App\Media;

use League\MimeTypeDetection\FinfoMimeTypeDetector;

class MimeType
{
    /**
     * @return string[]
     */
    public static function getProcessableTypes(): array
    {
        return [
            'audio/mp4', // m4a mp4a
            'audio/mpeg', // mpga mp2 mp2a mp3 m2a m3a
            'audio/ogg', // oga ogg spx
            'audio/x-aac', // aac
            'audio/x-flac', // flac
            'audio/x-wav', // wav
        ];
    }

    public static function getMimeTypeFromFile(string $path): string
    {
        $detector = new FinfoMimeTypeDetector();

        return $detector->detectMimeTypeFromFile($path)
            ?? self::getMimeTypeFromPath($path);
    }

    public static function getMimeTypeFromPath(string $path): string
    {
        $detector = new FinfoMimeTypeDetector();

        return $detector->detectMimeTypeFromPath($path)
            ?? 'application/octet-stream';
    }

    public static function isPathProcessable(string $path): bool
    {
        $mimeType = self::getMimeTypeFromPath($path);

        return in_array($mimeType, self::getProcessableTypes(), true);
    }

    public static function isFileProcessable(string $path): bool
    {
        $mimeType = self::getMimeTypeFromFile($path);

        return in_array($mimeType, self::getProcessableTypes(), true);
    }
}
