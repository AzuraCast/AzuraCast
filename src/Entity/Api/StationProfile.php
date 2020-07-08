<?php
namespace App\Entity\Api;

class StationProfile extends NowPlaying
{
    public StationServiceStatus $services;

    /** @var StationSchedule[] */
    public array $schedule = [];
}