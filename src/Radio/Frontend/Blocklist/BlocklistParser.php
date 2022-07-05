<?php

declare(strict_types=1);

namespace App\Radio\Frontend\Blocklist;

use App\Entity;
use App\Radio\Enums\FrontendAdapters;
use App\Service\IpGeolocation;
use InvalidArgumentException;
use PhpIP\IP;
use PhpIP\IPBlock;

final class BlocklistParser
{
    public function __construct(
        private readonly IpGeolocation $ipGeolocation
    ) {
    }

    public function isAllowed(
        string $ip,
        string $userAgent,
        Entity\Station $station
    ): bool {
        if ($this->isIpExplicitlyAllowed($ip, $station)) {
            return true;
        }
        if ($this->isIpExplicitlyBanned($ip, $station)) {
            return false;
        }
        if ($this->isCountryBanned($ip, $station)) {
            return false;
        }
        if ($this->isUserAgentBanned($userAgent, $station)) {
            return false;
        }
        return true;
    }

    public function isIpExplicitlyAllowed(
        string $ip,
        Entity\Station $station
    ): bool {
        if (FrontendAdapters::Remote === $station->getFrontendTypeEnum()) {
            return false;
        }

        $allowedIps = $station->getFrontendConfig()->getAllowedIps() ?? '';
        return $this->isIpInList($ip, $allowedIps);
    }

    public function isIpExplicitlyBanned(
        string $ip,
        Entity\Station $station
    ): bool {
        if (FrontendAdapters::Remote === $station->getFrontendTypeEnum()) {
            return false;
        }

        $bannedIps = $station->getFrontendConfig()->getBannedIps() ?? '';
        return $this->isIpInList($ip, $bannedIps);
    }

    protected function isIpInList(
        string $listenerIp,
        string $ipList
    ): bool {
        if (empty($ipList)) {
            return false;
        }

        foreach (array_filter(array_map('trim', explode("\n", $ipList))) as $ip) {
            try {
                if (!str_contains($ip, '/')) {
                    $ipObj = IP::create($ip);
                    if ($ipObj->matches($listenerIp)) {
                        return true;
                    }
                } else {
                    // Iterate through CIDR notation
                    foreach (IPBlock::create($ip) as $ipObj) {
                        if ($ipObj->matches($listenerIp)) {
                            return true;
                        }
                    }
                }
            } catch (InvalidArgumentException) {
            }
        }

        return false;
    }

    public function isCountryBanned(
        string $listenerIp,
        Entity\Station $station
    ): bool {
        if (FrontendAdapters::Remote === $station->getFrontendTypeEnum()) {
            return false;
        }

        $bannedCountries = $station->getFrontendConfig()->getBannedCountries() ?? [];
        if (empty($bannedCountries)) {
            return false;
        }

        $listenerLocation = $this->ipGeolocation->getLocationInfo($listenerIp);

        if (null !== $listenerLocation->country) {
            foreach ($bannedCountries as $countryCode) {
                if ($countryCode === $listenerLocation->country) {
                    return true;
                }
            }
        }

        return false;
    }

    public function isUserAgentBanned(
        string $listenerUserAgent,
        Entity\Station $station
    ): bool {
        if (FrontendAdapters::Remote === $station->getFrontendTypeEnum()) {
            return false;
        }

        $bannedUserAgents = $station->getFrontendConfig()->getBannedUserAgents() ?? '';
        if (empty($bannedUserAgents)) {
            return false;
        }

        foreach (array_filter(array_map('trim', explode("\n", $bannedUserAgents))) as $userAgent) {
            if (fnmatch($userAgent, $listenerUserAgent)) {
                return true;
            }
        }

        return false;
    }
}
