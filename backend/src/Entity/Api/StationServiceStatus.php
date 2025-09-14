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
        public bool $backendRunning,
        #[OA\Property(example: true)]
        public bool $frontendRunning
    ) {
    }
}
