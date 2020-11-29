<?php

namespace App\Service;

use App\Service\IpGeolocator;
use Exception;
use MaxMind\Db\Reader;

class IpGeolocation
{
    protected bool $isInitialized = false;

    protected ?Reader $reader;

    protected ?string $attribution;

    protected function initialize(): void
    {
        if ($this->isInitialized) {
            return;
        }

        $this->isInitialized = true;

        $readers = [
            IpGeolocator\GeoLite::class,
            IpGeolocator\DbIp::class,
        ];

        foreach ($readers as $reader) {
            /** @var IpGeolocator\IpGeolocatorInterface $reader */
            if ($reader::isAvailable()) {
                $this->reader = $reader::getReader();
                $this->attribution = $reader::getAttribution();
                return;
            }
        }

        $this->reader = null;
        $this->attribution = __(
            'GeoLite database not configured for this installation. See System Administration for instructions.'
        );
    }

    public function getAttribution(): string
    {
        if (!$this->isInitialized) {
            $this->initialize();
        }

        return $this->attribution;
    }

    /**
     * @return mixed[]
     */
    public function getLocationInfo(string $ip, string $locale): array
    {
        if (!$this->isInitialized) {
            $this->initialize();
        }

        if (null === $this->reader) {
            return [
                'status' => 'error',
                'message' => $this->getAttribution(),
            ];
        }

        try {
            $ipInfo = $this->reader->get($ip);
        } catch (Exception $e) {
            return [
                'status' => 'error',
                'message' => $e->getMessage(),
            ];
        }

        if (empty($ipInfo)) {
            return [
                'status' => 'error',
                'message' => 'Internal/Reserved IP',
            ];
        }

        return [
            'status' => 'success',
            'lat' => $ipInfo['location']['latitude'] ?? 0.0,
            'lon' => $ipInfo['location']['longitude'] ?? 0.0,
            'timezone' => $ipInfo['location']['time_zone'] ?? '',
            'region' => $this->getLocalizedString($ipInfo['subdivisions'][0]['names'] ?? null, $locale),
            'country' => $this->getLocalizedString($ipInfo['country']['names'] ?? null, $locale),
            'city' => $this->getLocalizedString($ipInfo['city']['names'] ?? null, $locale),
            'message' => $this->attribution,
        ];
    }

    protected function getLocalizedString($names, string $locale): string
    {
        if (empty($names)) {
            return '';
        }

        // Convert "en_US" to "en-US", the format MaxMind uses.
        $locale = str_replace('_', '-', $locale);

        // Check for an exact match.
        if (isset($names[$locale])) {
            return $names[$locale];
        }

        // Check for a match of the first portion, i.e. "en"
        $locale = strtolower(substr($locale, 0, 2));
        return $names[$locale] ?? $names['en'];
    }
}
