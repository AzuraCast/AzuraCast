<?php

declare(strict_types=1);

namespace App\Entity\Api\Vue;

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
        public string $platform,
        #[OA\Property]
        public bool $showCharts,
        #[OA\Property]
        public bool $showAlbumArt,
    ) {
    }
}
