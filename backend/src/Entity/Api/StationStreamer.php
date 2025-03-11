<?php

declare(strict_types=1);

namespace App\Entity\Api;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Api_StationStreamer',
    required: ['*'],
    type: 'object'
)]
final readonly class StationStreamer
{
    public function __construct(
        #[OA\Property]
        public int $id,
        #[OA\Property]
        public string $streamer_username,
        #[OA\Property]
        public string $display_name
    ) {
    }
}
