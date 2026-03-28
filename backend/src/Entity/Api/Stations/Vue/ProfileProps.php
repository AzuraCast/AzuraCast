<?php

declare(strict_types=1);

namespace App\Entity\Api\Stations\Vue;

use App\Entity\Api\NowPlaying\Vue\NowPlayingProps;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Api_Stations_Vue_ProfileProps',
    required: ['*'],
    type: 'object'
)]
class ProfileProps
{
    public function __construct(
        #[OA\Property]
        public NowPlayingProps $nowPlayingProps,
        #[OA\Property]
        public string $publicPageEmbedUrl,
        #[OA\Property]
        public string $publicOnDemandEmbedUrl,
        #[OA\Property]
        public string $publicRequestEmbedUrl,
        #[OA\Property]
        public string $publicHistoryEmbedUrl,
        #[OA\Property]
        public string $publicScheduleEmbedUrl,
        #[OA\Property]
        public string $publicPodcastsEmbedUrl,
        #[OA\Property]
        public string $frontendAdminUri,
        #[OA\Property]
        public string $frontendAdminPassword,
        #[OA\Property]
        public string $frontendSourcePassword,
        #[OA\Property]
        public string $frontendRelayPassword,
        #[OA\Property]
        public ?int $frontendPort
    ) {
    }
}
