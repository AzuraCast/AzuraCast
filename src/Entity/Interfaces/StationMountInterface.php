<?php

declare(strict_types=1);

namespace App\Entity\Interfaces;

interface StationMountInterface
{
    public const FORMAT_MP3 = 'mp3';
    public const FORMAT_OGG = 'ogg';
    public const FORMAT_AAC = 'aac';
    public const FORMAT_OPUS = 'opus';
    public const FORMAT_FLAC = 'flac';

    public const PROTOCOL_ICY = 'icy';
    public const PROTOCOL_HTTP = 'http';
    public const PROTOCOL_HTTPS = 'https';

    public function getEnableAutodj(): bool;

    public function getAutodjUsername(): ?string;

    public function getAutodjPassword(): ?string;

    public function getAutodjBitrate(): ?int;

    public function getAutodjFormat(): ?string;

    public function getAutodjHost(): ?string;

    public function getAutodjPort(): ?int;

    public function getAutodjProtocol(): ?string;

    public function getAutodjMount(): ?string;

    public function getAutodjAdapterType(): string;

    public function getIsPublic(): bool;
}
