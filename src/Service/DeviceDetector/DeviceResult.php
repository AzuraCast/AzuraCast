<?php

namespace App\Service\DeviceDetector;

use DeviceDetector\DeviceDetector;

final class DeviceResult
{
    public function __construct(
        protected bool $isBot = false,
        protected bool $isMobile = false,
        protected ?string $client = null
    ) {
    }

    public function isBot(): bool
    {
        return $this->isBot;
    }

    public function isMobile(): bool
    {
        return $this->isMobile;
    }

    public function getClient(): ?string
    {
        return $this->client;
    }

    public static function fromDeviceDetector(DeviceDetector $dd): self
    {
        $isBot = $dd->isBot();

        if ($isBot) {
            $clientBot = (array)$dd->getBot();

            $clientBotName = $clientBot['name'] ?? 'Unknown Crawler';
            $clientBotType = $clientBot['category'] ?? 'Generic Crawler';
            $client = $clientBotName . ' (' . $clientBotType . ')';
        } else {
            $clientInfo = (array)$dd->getClient();
            $clientBrowser = $clientInfo['name'] ?? 'Unknown Browser';
            $clientVersion = $clientInfo['version'] ?? '0.00';

            $clientOsInfo = (array)$dd->getOs();
            $clientOs = $clientOsInfo['name'] ?? 'Unknown OS';

            $client = $clientBrowser . ' ' . $clientVersion . ', ' . $clientOs;
        }

        return new self(
            isBot: $isBot,
            isMobile: $dd->isMobile(),
            client: $client
        );
    }
}
