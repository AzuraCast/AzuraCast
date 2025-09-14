<?php

declare(strict_types=1);

namespace App\Entity\Api;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Api_StationRequest',
    type: 'object'
)]
final class StationRequest
{
    #[OA\Property(
        description: 'Requestable ID unique identifier',
        example: 1
    )]
    public string $request_id;

    #[OA\Property(
        description: 'URL to directly submit request',
        example: '/api/station/1/request/1'
    )]
    public string $request_url;

    #[OA\Property]
    public Song $song;
}
