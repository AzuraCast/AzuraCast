<?php

declare(strict_types=1);

namespace App\Entity\Api;

use App\Entity\Api\Traits\HasLinks;
use DateTimeImmutable;
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
        #[OA\Property(type: 'string', format: 'date-time')]
        public readonly DateTimeImmutable $timestampStart,
        #[OA\Property(type: 'string', format: 'date-time')]
        public readonly ?DateTimeImmutable $timestampEnd,
        #[OA\Property]
        public ?StationStreamer $streamer = null,
        #[OA\Property]
        public ?StationStreamerBroadcastRecording $recording = null,
    ) {
    }
}
