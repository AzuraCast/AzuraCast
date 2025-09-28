<?php

declare(strict_types=1);

namespace App\Entity\Api\Vue;

use App\Entity\Api\HashMap;
use App\Entity\Enums\AnalyticsLevel;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Vue_DashboardGlobals',
    required: ['*'],
    type: 'object'
)]
final class DashboardGlobals
{
    public function __construct(
        #[OA\Property]
        public string $instanceName,
        #[OA\Property]
        public string $homeUrl,
        #[OA\Property]
        public string $logoutUrl,
        #[OA\Property]
        public string $version,
        #[OA\Property]
        public bool $isDocker,
        #[OA\Property]
        public string $platform,
        #[OA\Property]
        public bool $showCharts,
        #[OA\Property]
        public bool $showAlbumArt,
        #[OA\Property(
            type: 'object',
            additionalProperties: new OA\AdditionalProperties(
                type: 'string'
            )
        )]
        public HashMap $supportedLocales,
        #[OA\Property]
        public AnalyticsLevel $analyticsLevel,
    ) {
    }
}
