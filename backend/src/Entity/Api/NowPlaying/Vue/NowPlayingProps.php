<?php

declare(strict_types=1);

namespace App\Entity\Api\NowPlaying\Vue;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Api_NowPlaying_Vue_Props',
    required: ['*'],
    type: 'object'
)]
final class NowPlayingProps
{
    public function __construct(
        #[OA\Property]
        public string $stationShortName,
        #[OA\Property]
        public bool $useStatic = false,
        #[OA\Property]
        public bool $useSse = false
    ) {
    }
}
