<?php
namespace App\Entity;

use App\Collection;

class StationFrontendConfiguration extends Collection
{
    public const CUSTOM_CONFIGURATION = 'custom_config';

    public function getCustomConfiguration(): ?string
    {
        return $this->data[self::CUSTOM_CONFIGURATION];
    }

    public function setCustomConfiguration(?string $config): void
    {
        $this->data[self::CUSTOM_CONFIGURATION] = $config;
    }

    public const SOURCE_PASSWORD = 'source_pw';

    public function getSourcePassword(): ?string
    {
        return $this->data[self::SOURCE_PASSWORD];
    }

    public function setSourcePassword(?string $pw): void
    {
        $this->data[self::SOURCE_PASSWORD] = $pw;
    }

    public const ADMIN_PASSWORD = 'admin_pw';

    public function getAdminPassword(): ?string
    {
        return $this->data[self::ADMIN_PASSWORD];
    }

    public function setAdminPassword(?string $pw): void
    {
        $this->data[self::ADMIN_PASSWORD] = $pw;
    }

    public const RELAY_PASSWORD = 'relay_pw';

    public function getRelayPassword(): ?string
    {
        return $this->data[self::RELAY_PASSWORD];
    }

    public function setRelayPassword(?string $pw): void
    {
        $this->data[self::RELAY_PASSWORD] = $pw;
    }

    public const STREAMER_PASSWORD = 'streamer_pw';

    public function getStreamerPassword(): ?string
    {
        return $this->data[self::STREAMER_PASSWORD];
    }

    public function setStreamerPassword(?string $pw): void
    {
        $this->data[self::STREAMER_PASSWORD] = $pw;
    }

    public const PORT = 'port';

    public function getPort(): ?int
    {
        $port = $this->data[self::PORT];
        return is_numeric($port) ? (int)$port : null;
    }

    public function setPort(?int $port): void
    {
        $this->data[self::PORT] = $port;
    }

    public const MAX_LISTENERS = 'max_listeners';

    public function getMaxListeners(): ?int
    {
        $listeners = $this->data[self::MAX_LISTENERS];
        return is_numeric($listeners) ? (int)$listeners : null;
    }

    public function setMaxListeners(?int $listeners): void
    {
        $this->data[self::MAX_LISTENERS] = $listeners;
    }

    public const BANNED_IPS = 'banned_ips';

    public function getBannedIps(): ?string
    {
        return $this->data[self::BANNED_IPS];
    }

    public function setBannedIps(?string $ips): void
    {
        $this->data[self::BANNED_IPS] = $ips;
    }
}