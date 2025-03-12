<?php

declare(strict_types=1);

namespace App\Entity\Api;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Api_StationServiceStatus',
    required: ['*'],
    type: 'object'
)]
final readonly class StationServiceStatus
{
    public function __construct(
        #[OA\Property(example: true)]
        public bool $backend_running,
        #[OA\Property(example: true)]
        public bool $frontend_running,
        #[OA\Property(example: true)]
        public bool $station_has_started,
        #[OA\Property(example: true)]
        public bool $station_needs_restart
    ) {
    }
}
