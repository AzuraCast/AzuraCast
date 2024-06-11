<?php

declare(strict_types=1);

namespace App\Service\DeviceDetector;

use DeviceDetector\DeviceDetector;
use DeviceDetector\Parser\Client\Browser;
use DeviceDetector\Parser\OperatingSystem;

final class DeviceResult
{
    public function __construct(
        public readonly string $userAgent
    ) {
    }

    public ?string $client = null;

    public bool $isBrowser = false;

    public bool $isMobile = false;

    public bool $isBot = false;

    public ?string $browserFamily = null;

    public ?string $osFamily = null;

    public static function fromDeviceDetector(string $userAgent, DeviceDetector $dd): self
    {
        $record = new self($userAgent);
        $record->isBot = $dd->isBot();

        if ($record->isBot) {
            $clientBot = (array)$dd->getBot();
            $clientBotName = $clientBot['name'] ?? 'Unknown Crawler';
            $clientBotType = $clientBot['category'] ?? 'Generic Crawler';
            $record->client = $clientBotName . ' (' . $clientBotType . ')';

            $record->browserFamily = 'Crawler';
            $record->osFamily = 'Crawler';
        } else {
            $record->isMobile = $dd->isMobile();
            $record->isBrowser = $dd->isBrowser();

            $clientInfo = (array)$dd->getClient();
            $clientBrowser = $clientInfo['name'] ?? 'Unknown Browser';
            $clientVersion = $clientInfo['version'] ?? '0.00';
            $record->browserFamily = Browser::getBrowserFamily($clientBrowser);

            $clientOsInfo = (array)$dd->getOs();
            $clientOs = $clientOsInfo['name'] ?? 'Unknown OS';
            $record->osFamily = OperatingSystem::getOsFamily($clientOs);

            $record->client = $clientBrowser . ' ' . $clientVersion . ', ' . $clientOs;
        }

        return $record;
    }
}
