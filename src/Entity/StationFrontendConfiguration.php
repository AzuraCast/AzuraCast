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

    public const BANNED_COUNTRIES = 'banned_countries';

    public function getBannedCountries(): ?array
    {
        return $this->get(self::BANNED_COUNTRIES);
    }

    public function setBannedCountries(?array $countries): void
    {
        $this->set(self::BANNED_COUNTRIES, $countries);
    }

    public const ALLOWED_IPS = 'allowed_ips';

    public function getAllowedIps(): ?string
    {
        return $this->get(self::ALLOWED_IPS);
    }

    public function setAllowedIps(?string $ips): void
    {
        $this->set(self::ALLOWED_IPS, $ips);
    }

    public const SC_LICENSE_ID = 'sc_license_id';

    public function getScLicenseId(): ?string
    {
        return $this->get(self::SC_LICENSE_ID);
    }

    public function setScLicenseId(?string $licenseId): void
    {
        $this->set(self::SC_LICENSE_ID, $licenseId);
    }

    public const SC_USER_ID = 'sc_user_id';

    public function getScUserId(): ?string
    {
        return $this->get(self::SC_USER_ID);
    }

    public function setScUserId(?string $userId): void
    {
        $this->set(self::SC_USER_ID, $userId);
    }
}
