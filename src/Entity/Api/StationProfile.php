<?php

declare(strict_types=1);

namespace App\Entity\Api;

use App\Entity\Api\NowPlaying\NowPlaying;
use App\Traits\LoadFromParentObject;

final class StationProfile extends NowPlaying
{
    use LoadFromParentObject;

    public StationServiceStatus $services;

    /** @var StationSchedule[] */
    public array $schedule = [];
}
