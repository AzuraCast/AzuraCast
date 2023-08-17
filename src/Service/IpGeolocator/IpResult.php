<?php

declare(strict_types=1);

namespace App\Service\IpGeolocator;

final class IpResult
{
    public function __construct(
        public readonly string $ip
    ) {
    }

    public string $description;

    public ?string $region = null;

    public ?string $city = null;

    public ?string $country = null;

    public ?float $lat = null;

    public ?float $lon = null;

    public static function fromIpInfo(string $ip, array $ipInfo = []): self
    {
        $record = new self($ip);

        if (isset($ipInfo['status']) && $ipInfo['status'] === 'error') {
            $record->description = 'Internal/Reserved IP';
            return $record;
        }

        $record->region = $ipInfo['subdivisions'][0]['names']['en'] ?? null;
        $record->city = $ipInfo['city']['names']['en'] ?? null;
        $record->country = $ipInfo['country']['iso_code'] ?? null;
        $record->description = implode(
            ', ',
            array_filter([
                $record->city,
                $record->region,
                $record->country,
            ])
        );

        $record->lat = $ipInfo['location']['latitude'] ?? null;
        $record->lon = $ipInfo['location']['longitude'] ?? null;
        return $record;
    }
}
