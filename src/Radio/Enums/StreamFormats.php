<?php

// phpcs:ignoreFile

declare(strict_types=1);

namespace App\Radio\Enums;

enum StreamFormats: string
{
    case Mp3 = 'mp3';
    case Ogg = 'ogg';
    case Aac = 'aac';
    case Opus = 'opus';
    case Flac = 'flac';

    public function getExtension(): string
    {
        return match ($this) {
            self::Aac => 'mp4',
            self::Ogg => 'ogg',
            self::Opus => 'opus',
            self::Flac => 'flac',
            default => 'mp3',
        };
    }

    public function sendIcyMetadata(): bool
    {
        return match ($this) {
            self::Opus, self::Flac => true,
            default => false,
        };
    }

    public static function default(): self
    {
        return self::Mp3;
    }
}
