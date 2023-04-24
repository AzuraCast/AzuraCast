<?php

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
        return $this->value;
    }

    public function formatBitrate(?int $bitrate): string
    {
        if (null === $bitrate) {
            return strtoupper($this->value);
        }

        return match ($this) {
            self::Flac => 'FLAC',
            default => $bitrate . 'kbps ' . strtoupper($this->value)
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
