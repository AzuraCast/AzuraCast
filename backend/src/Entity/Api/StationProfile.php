<?php

declare(strict_types=1);

namespace App\Entity\Api;

use App\Entity\Api\NowPlaying\Station;
use Psr\Http\Message\UriInterface;

final class StationProfile
{
    public Station $station;

    public StationServiceStatus $services;

    /** @var StationSchedule[] */
    public array $schedule = [];

    /**
     * Iterate through sub-items and re-resolve any Uri instances to reflect base URL changes.
     */
    public function resolveUrls(UriInterface $base): void
    {
        $this->station->resolveUrls($base);
    }
}
