<?php

declare(strict_types=1);

namespace App\Entity\Interfaces;

use App\Radio\Enums\AdapterTypeInterface;
use App\Radio\Enums\StreamFormats;
use App\Radio\Enums\StreamProtocols;

interface StationMountInterface
{
    public function getEnableAutodj(): bool;

    public function getAutodjUsername(): ?string;

    public function getAutodjPassword(): ?string;

    public function getAutodjBitrate(): ?int;

    public function getAutodjFormatEnum(): ?StreamFormats;

    public function getAutodjHost(): ?string;

    public function getAutodjPort(): ?int;

    public function getAutodjProtocolEnum(): ?StreamProtocols;

    public function getAutodjMount(): ?string;

    public function getAutodjAdapterTypeEnum(): AdapterTypeInterface;

    public function getIsPublic(): bool;
}
