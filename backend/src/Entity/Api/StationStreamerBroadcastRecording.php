<?php

declare(strict_types=1);

namespace App\Entity\Api;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Api_StationStreamerBroadcastRecording',
    required: ['*'],
    type: 'object'
)]
final readonly class StationStreamerBroadcastRecording
{
    public function __construct(
        #[OA\Property]
        public string $path,
        #[OA\Property]
        public int $size,
        #[OA\Property]
        public string $downloadUrl,
    ) {
    }
}
