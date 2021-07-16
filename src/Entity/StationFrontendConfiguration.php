<?php

declare(strict_types=1);

namespace App\Entity;

use App\Utilities\Strings;
use Doctrine\Common\Collections\ArrayCollection;

class StationFrontendConfiguration extends ArrayCollection
{
    public function __construct(array $elements = [])
    {
        // Generate defaults if not set.
        $autoAssignPasswords = [
            self::SOURCE_PASSWORD,
            self::ADMIN_PASSWORD,
            self::RELAY_PASSWORD,
            self::STREAMER_PASSWORD,
        ];

        foreach ($autoAssignPasswords as $autoAssignPassword) {
            if (empty($elements[$autoAssignPassword])) {
                $elements[$autoAssignPassword] = Strings::generatePassword();
            }
        }

        parent::__construct($elements);
    }

    public const CUSTOM_CONFIGURATION = 'custom_config';

    public function getCustomConfiguration(): ?string
    {
        return $this->get(self::CUSTOM_CONFIGURATION);
    }

    public function setCustomConfiguration(?string $config): void
    {
        $this->set(self::CUSTOM_CONFIGURATION, $config);
    }

    public const SOURCE_PASSWORD = 'source_pw';

    public function getSourcePassword(): string
    {
        return $this->get(self::SOURCE_PASSWORD);
    }

    public function setSourcePassword(string $pw): void
    {
        $this->set(self::SOURCE_PASSWORD, $pw);
    }

    public const ADMIN_PASSWORD = 'admin_pw';

    public function getAdminPassword(): string
    {
        return $this->get(self::ADMIN_PASSWORD);
    }

    public function setAdminPassword(string $pw): void
    {
        $this->set(self::ADMIN_PASSWORD, $pw);
    }

    public const RELAY_PASSWORD = 'relay_pw';

    public function getRelayPassword(): string
    {
        return $this->get(self::RELAY_PASSWORD);
    }

    public function setRelayPassword(string $pw): void
    {
        $this->set(self::RELAY_PASSWORD, $pw);
    }

    public const STREAMER_PASSWORD = 'streamer_pw';

    public function getStreamerPassword(): string
    {
        return $this->get(self::STREAMER_PASSWORD);
    }

    public function setStreamerPassword(string $pw): void
    {
        $this->set(self::STREAMER_PASSWORD, $pw);
    }

    public const PORT = 'port';

    public function getPort(): ?int
    {
        $port = $this->get(self::PORT);
        return is_numeric($port) ? (int)$port : null;
    }

    public function setPort(?int $port): void
    {
        $this->set(self::PORT, $port);
    }

    public const MAX_LISTENERS = 'max_listeners';

    public function getMaxListeners(): ?int
    {
        $listeners = $this->get(self::MAX_LISTENERS);
        return is_numeric($listeners) ? (int)$listeners : null;
    }

    public function setMaxListeners(?int $listeners): void
    {
        $this->set(self::MAX_LISTENERS, $listeners);
    }

    public const BANNED_IPS = 'banned_ips';

    public function getBannedIps(): ?string
    {
        return $this->get(self::BANNED_IPS);
    }

    public function setBannedIps(?string $ips): void
    {
        $this->set(self::BANNED_IPS, $ips);
    }
}
