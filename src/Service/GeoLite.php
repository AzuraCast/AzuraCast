<?php
namespace App\Service;

use App\Settings;
use Cake\Chronos\Chronos;
use MaxMind\Db\Reader;

class GeoLite
{
    protected Settings $settings;

    protected ?Reader $reader = null;

    public function __construct(Settings $settings)
    {
        $this->settings = $settings;

        $mmdbPath = $this->getDatabasePath();
        if (file_exists($mmdbPath)) {
            $this->reader = new Reader($mmdbPath);
        }
    }

    public function getDatabasePath(): string
    {
        return dirname($this->settings[Settings::BASE_DIR]) . '/geoip/GeoLite2-City.mmdb';
    }

    public function getVersion(): ?string
    {
        if (null === $this->reader) {
            return null;
        }

        $metadata = $this->reader->metadata();

        $buildDate = Chronos::createFromTimestampUTC($metadata->buildEpoch);
        return $metadata->databaseType . ' (' . $buildDate->format('Y-m-d') . ')';
    }

    public function getLocationInfo(string $ip, string $locale): array
    {
        if (null === $this->reader) {
            return [
                'status' => 'error',
                'message' => 'GeoLite database not configured for this installation. See System Administration for instructions.',
            ];
        }

        try {
            $ipInfo = $this->reader->get($ip);
        } catch (\Exception $e) {
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
            'message' => 'This product includes GeoLite2 data created by MaxMind, available from <a href="http://www.maxmind.com">http://www.maxmind.com</a>.',
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