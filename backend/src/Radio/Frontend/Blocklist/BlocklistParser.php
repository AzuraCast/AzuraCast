<?php

declare(strict_types=1);

namespace App\Radio\Frontend\Blocklist;

use App\Entity\Station;
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
        Station $station,
        string $ip,
        ?string $userAgent = null
    ): bool {
        if ($this->hasAllowedIps($station)) {
            return $this->isIpExplicitlyAllowed($station, $ip);
        }

        if ($this->isIpExplicitlyBanned($station, $ip)) {
            return false;
        }

        if ($this->isCountryBanned($station, $ip)) {
            return false;
        }

        if (null !== $userAgent && $this->isUserAgentBanned($station, $userAgent)) {
            return false;
        }

        return true;
    }

    private function hasAllowedIps(Station $station): bool
    {
        if (FrontendAdapters::Remote === $station->getFrontendType()) {
            return false;
        }

        $allowedIps = trim($station->getFrontendConfig()->getAllowedIps() ?? '');
        return !empty($allowedIps);
    }

    private function isIpExplicitlyAllowed(
        Station $station,
        string $ip
    ): bool {
        if (FrontendAdapters::Remote === $station->getFrontendType()) {
            return false;
        }

        $allowedIps = $station->getFrontendConfig()->getAllowedIps() ?? '';
        return $this->isIpInList($ip, $allowedIps);
    }

    private function isIpExplicitlyBanned(
        Station $station,
        string $ip
    ): bool {
        if (FrontendAdapters::Remote === $station->getFrontendType()) {
            return false;
        }

        $bannedIps = $station->getFrontendConfig()->getBannedIps() ?? '';
        return $this->isIpInList($ip, $bannedIps);
    }

    private function isIpInList(
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

    private function isCountryBanned(
        Station $station,
        string $listenerIp
    ): bool {
        if (FrontendAdapters::Remote === $station->getFrontendType()) {
            return false;
        }

        $bannedCountries = $station->getFrontendConfig()->getBannedCountries() ?? [];
        if (empty($bannedCountries)) {
            return false;
        }

        $listenerLocation = $this->ipGeolocation->getLocationInfo($listenerIp);

        return (null !== $listenerLocation->country)
            && in_array($listenerLocation->country, $bannedCountries, true);
    }

    public function isUserAgentBanned(
        Station $station,
        string $listenerUserAgent
    ): bool {
        if (FrontendAdapters::Remote === $station->getFrontendType()) {
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
