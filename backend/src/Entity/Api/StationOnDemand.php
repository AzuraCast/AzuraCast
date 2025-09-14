<?php

declare(strict_types=1);

namespace App\Entity\Api;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Api_StationOnDemand',
    type: 'object'
)]
final class StationOnDemand
{
    #[OA\Property(
        description: 'Track ID unique identifier',
        example: 1
    )]
    public string $track_id;

    #[OA\Property(
        description: 'URL to download/play track.',
        example: '/api/station/1/ondemand/download/1'
    )]
    public string $download_url;

    #[OA\Property]
    public Song $media;

    #[OA\Property]
    public string $playlist;
}
