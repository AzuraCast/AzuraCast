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
        public string $publicPageUri,
        #[OA\Property]
        public string $publicPageEmbedUri,
        #[OA\Property]
        public string $publicWebDjUri,
        #[OA\Property]
        public string $publicOnDemandUri,
        #[OA\Property]
        public string $publicPodcastsUri,
        #[OA\Property]
        public string $publicScheduleUri,
        #[OA\Property]
        public string $publicOnDemandEmbedUri,
        #[OA\Property]
        public string $publicRequestEmbedUri,
        #[OA\Property]
        public string $publicHistoryEmbedUri,
        #[OA\Property]
        public string $publicScheduleEmbedUri,
        #[OA\Property]
        public string $publicPodcastsEmbedUri,
        #[OA\Property]
        public string $frontendAdminUri,
        #[OA\Property]
        public string $frontendAdminPassword,
        #[OA\Property]
        public string $frontendSourcePassword,
        #[OA\Property]
        public string $frontendRelayPassword,
        #[OA\Property]
        public ?int $frontendPort,
        #[OA\Property]
        public int $numSongs,
        #[OA\Property]
        public int $numPlaylists
    ) {
    }
}
