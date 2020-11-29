<?php

namespace App\Entity\Api;

use App\Traits\LoadFromParentObject;

class StationProfile extends NowPlaying
{
    use LoadFromParentObject;

    public StationServiceStatus $services;

    /** @var StationSchedule[] */
    public array $schedule = [];
}
