<?php

declare(strict_types=1);

namespace App\Media;

use League\MimeTypeDetection\FinfoMimeTypeDetector;

final class MimeType
{
    /**
     * @return string[]
     */
    public static function getProcessableTypes(): array
    {
        return [
            'audio/aiff', // aiff (Audio Interchange File Format)
            'audio/flac', // MIME type used by some FLAC files
            'audio/mp4', // m4a mp4a
            'audio/mpeg', // mpga mp2 mp2a mp3 m2a m3a
            'audio/ogg', // oga ogg spx
            'audio/s3m', // s3m (ScreamTracker 3 Module)
            'audio/wav', // wav
            'audio/xm', // xm
            'audio/vnd.wave', // alt for wav (RFC 2361)
            'audio/x-aac', // aac
            'audio/x-aiff', // alt for aiff
            'audio/x-flac', // flac
            'audio/x-m4a', // alt for m4a/mp4a
            'audio/x-mod', // stm, alt for xm
            'audio/x-s3m', // alt for s3m
            'audio/x-wav', // alt for wav
            'audio/x-ms-wma', // wma (Windows Media Audio)
            'video/mp4', // some MP4 audio files are recognized as this (#3569)
            'video/x-ms-asf', // asf / wmv / alt for wma
        ];
    }

    public static function getMimeTypeFromFile(string $path): string
    {
        $fileMimeType = (new FinfoMimeTypeDetector(
            extensionMap: new MimeTypeExtensionMap()
        ))->detectMimeTypeFromFile($path);

        if ('application/octet-stream' === $fileMimeType) {
            $fileMimeType = null;
        }

        return $fileMimeType ?? self::getMimeTypeFromPath($path);
    }

    public static function getMimeTypeFromPath(string $path): string
    {
        $extensionMap = new MimeTypeExtensionMap();

        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        return $extensionMap->lookupMimeType($extension) ?? 'application/octet-stream';
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
