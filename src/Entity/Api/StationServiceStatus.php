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
        bool $backend_running,
        bool $frontend_running,
        bool $station_has_started,
        bool $station_needs_restart
    ) {
        $this->backend_running = $backend_running;
        $this->frontend_running = $frontend_running;
        $this->station_has_started = $station_has_started;
        $this->station_needs_restart = $station_needs_restart;
    }
}
