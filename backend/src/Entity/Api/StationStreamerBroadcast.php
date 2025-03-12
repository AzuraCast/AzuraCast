<?php

declare(strict_types=1);

namespace App\Entity\Api;

use App\Entity\Api\Traits\HasLinks;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Api_StationStreamerBroadcast',
    required: ['*'],
    type: 'object'
)]
final class StationStreamerBroadcast
{
    use HasLinks;

    public function __construct(
        #[OA\Property]
        public readonly int $id,
        #[OA\Property(format: 'date-time')]
        public readonly string $timestampStart,
        #[OA\Property(format: 'date-time')]
        public readonly ?string $timestampEnd,
        #[OA\Property]
        public ?StationStreamer $streamer = null,
        #[OA\Property]
        public ?StationStreamerBroadcastRecording $recording = null,
    ) {
    }
}
