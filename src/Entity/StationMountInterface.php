<?php
namespace App\Entity;

interface StationMountInterface
{
    const FORMAT_MP3    = 'mp3';
    const FORMAT_OGG    = 'ogg';
    const FORMAT_AAC    = 'aac';
    const FORMAT_OPUS   = 'opus';

    public function getEnableAutodj(): bool;

    public function getAutodjUsername(): ?string;

    public function getAutodjPassword(): ?string;

    public function getAutodjBitrate(): ?int;

    public function getAutodjFormat(): ?string;

    public function getAutodjHost(): ?string;

    public function getAutodjPort(): ?int;

    public function getAutodjMount(): ?string;

    public function getAutodjShoutcastMode(): bool;

    public function getIsPublic(): bool;
}
