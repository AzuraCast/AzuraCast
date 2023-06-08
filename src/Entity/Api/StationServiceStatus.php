<?php

declare(strict_types=1);

namespace App\Entity\Api;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Api_StationServiceStatus',
    type: 'object'
)]
final class StationServiceStatus
{
    #[OA\Property(example: true)]
    public bool $backend_running;

    #[OA\Property(example: true)]
    public bool $frontend_running;

    #[OA\Property(example: true)]
    public bool $station_has_started;

    #[OA\Property(example: true)]
    public bool $station_needs_restart;

    public function __construct(
        bool $backendRunning,
        bool $frontendRunning,
        bool $stationHasStarted,
        bool $stationNeedsRestart
    ) {
        $this->backend_running = $backendRunning;
        $this->frontend_running = $frontendRunning;
        $this->station_has_started = $stationHasStarted;
        $this->station_needs_restart = $stationNeedsRestart;
    }
}
