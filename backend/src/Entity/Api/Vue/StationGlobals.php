<?php

declare(strict_types=1);

namespace App\Entity\Api\Vue;

use App\Radio\Enums\BackendAdapters;
use App\Radio\Enums\FrontendAdapters;
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
        public ?string $description,
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
        public bool $enableStreamers,
        #[OA\Property]
        public string $webDjUrl,
        #[OA\Property]
        public string $publicPodcastsUrl,
        #[OA\Property]
        public string $publicScheduleUrl,
        #[OA\Property]
        public bool $enableRequests,
        #[OA\Property]
        public StationGlobalFeatures $features,
        #[OA\Property]
        public string $ipGeoAttribution,
        #[OA\Property]
        public BackendAdapters $backendType,
        #[OA\Property]
        public FrontendAdapters $frontendType,
        #[OA\Property]
        public bool $canReload,
        #[OA\Property]
        public bool $useManualAutoDj
    ) {
    }
}
