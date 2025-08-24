<?php

declare(strict_types=1);

namespace App\Entity\Api\Vue;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Vue_StationGlobals',
    required: ['*'],
    type: 'object'
)]
final readonly class StationGlobals
{
    public function __construct(
        #[OA\Property]
        public int $id,
        #[OA\Property]
        public ?string $name,
        #[OA\Property]
        public string $shortName,
        #[OA\Property]
        public bool $isEnabled,
        #[OA\Property]
        public bool $hasStarted,
        #[OA\Property]
        public bool $needsRestart,
        #[OA\Property]
        public string $timezone,
        #[OA\Property]
        public ?string $offlineText,
        #[OA\Property]
        public int $maxBitrate,
        #[OA\Property]
        public int $maxMounts,
        #[OA\Property]
        public int $maxHlsStreams,
        #[OA\Property]
        public bool $enablePublicPages,
        #[OA\Property]
        public string $publicPageUrl,
        #[OA\Property]
        public bool $enableOnDemand,
        #[OA\Property]
        public string $onDemandUrl,
        #[OA\Property]
        public string $webDjUrl,
        #[OA\Property]
        public bool $enableRequests,
        #[OA\Property]
        public StationGlobalFeatures $features
    ) {
    }
}
