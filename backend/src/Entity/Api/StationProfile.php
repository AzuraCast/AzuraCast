<?php

declare(strict_types=1);

namespace App\Entity\Api;

use App\Entity\Api\NowPlaying\Station;

final class StationProfile
{
    public Station $station;

    public StationServiceStatus $services;

    /** @var StationSchedule[] */
    public array $schedule = [];
}
