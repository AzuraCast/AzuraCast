<?php

declare(strict_types=1);

namespace App\Entity\Api\Stations\Vue;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Api_Stations_Vue_StreamersProps',
    required: ['*'],
    type: 'object'
)]
final readonly class StreamersProps
{
    public function __construct(
        #[OA\Property]
        public bool $recordStreams,
        #[OA\Property]
        public string $connectionServerUrl,
        #[OA\Property]
        public ?int $connectionStreamPort,
        #[OA\Property]
        public ?string $connectionIp,
        #[OA\Property]
        public string $connectionDjMountPoint
    ) {
    }
}
