<?php

namespace App\Entity;

interface StationMountInterface
{
    public const FORMAT_MP3 = 'mp3';
    public const FORMAT_OGG = 'ogg';
    public const FORMAT_AAC = 'aac';
    public const FORMAT_OPUS = 'opus';

    public function getEnableAutodj(): bool;

    public function getAutodjUsername(): ?string;

    public function getAutodjPassword(): ?string;

    public function getAutodjBitrate(): ?int;

    public function getAutodjFormat(): ?string;

    public function getAutodjHost(): ?string;

    public function getAutodjPort(): ?int;

    public function getAutodjMount(): ?string;

    public function getAutodjAdapterType(): string;

    public function getIsPublic(): bool;
}
