<?php

declare(strict_types=1);

namespace App\Entity\Api\Vue;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Vue_StationFeatures',
    required: ['*'],
    type: 'object'
)]
final readonly class StationGlobalFeatures
{
    public function __construct(
        #[OA\Property]
        public bool $media = false,
        #[OA\Property]
        public bool $sftp = false,
        #[OA\Property]
        public bool $podcasts = false,
        #[OA\Property]
        public bool $streamers = false,
        #[OA\Property]
        public bool $webhooks = false,
        #[OA\Property]
        public bool $requests = false,
        #[OA\Property]
        public bool $mountPoints = false,
        #[OA\Property]
        public bool $hlsStreams = false,
        #[OA\Property]
        public bool $remoteRelays = false,
        #[OA\Property]
        public bool $customLiquidsoapConfig = false,
        #[OA\Property]
        public bool $autoDjQueue = false
    ) {
    }
}
