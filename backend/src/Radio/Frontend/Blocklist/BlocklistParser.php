<?php

declare(strict_types=1);

namespace App\Radio\Frontend\Blocklist;

use App\Entity\Station;
use App\Radio\Enums\FrontendAdapters;
use App\Service\IpGeolocation;
use InvalidArgumentException;
use PhpIP\IP;
use PhpIP\IPBlock;

final readonly class BlocklistParser
{
    public function __construct(
        private IpGeolocation $ipGeolocation
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
        if (FrontendAdapters::Remote === $station->frontend_type) {
            return false;
        }

        $allowedIps = trim($station->frontend_config->allowed_ips ?? '');
        return !empty($allowedIps);
    }

    private function isIpExplicitlyAllowed(
        Station $station,
        string $ip
    ): bool {
        if (FrontendAdapters::Remote === $station->frontend_type) {
            return false;
        }

        $allowedIps = $station->frontend_config->allowed_ips ?? '';
        return $this->isIpInList($ip, $allowedIps);
    }

    private function isIpExplicitlyBanned(
        Station $station,
        string $ip
    ): bool {
        if (FrontendAdapters::Remote === $station->frontend_type) {
            return false;
        }

        $bannedIps = $station->frontend_config->banned_ips ?? '';
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
        if (FrontendAdapters::Remote === $station->frontend_type) {
            return false;
        }

        $bannedCountries = $station->frontend_config->banned_countries ?? [];
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
        if (FrontendAdapters::Remote === $station->frontend_type) {
            return false;
        }

        $bannedUserAgents = $station->frontend_config->banned_user_agents ?? '';
        if (empty($bannedUserAgents)) {
            return false;
        }

        return array_any(
            array_filter(array_map('trim', explode("\n", $bannedUserAgents))),
            fn($userAgent) => fnmatch($userAgent, $listenerUserAgent)
        );
    }
}
