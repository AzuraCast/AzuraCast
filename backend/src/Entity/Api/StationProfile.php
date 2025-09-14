<?php

declare(strict_types=1);

namespace App\Entity\Api;

use App\Entity\Api\NowPlaying\Station;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Api_StationProfile',
    required: ['*'],
    type: 'object'
)]
final class StationProfile
{
    #[OA\Property]
    public Station $station;

    #[OA\Property]
    public StationServiceStatus $services;

    /** @var StationSchedule[] */
    #[OA\Property(
        type: 'array',
        items: new OA\Items(
            ref: StationSchedule::class
        )
    )]
    public array $schedule = [];
}
