<?php

declare(strict_types=1);

namespace App\Entity\Api;

use OpenApi\Attributes as OA;
use Psr\Http\Message\UriInterface;

#[OA\Schema(
    schema: 'Api_StationRequest',
    type: 'object'
)]
final class StationRequest implements ResolvableUrlInterface
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

    /**
     * Re-resolve any Uri instances to reflect base URL changes.
     *
     * @param UriInterface $base
     */
    public function resolveUrls(UriInterface $base): void
    {
        $this->song->resolveUrls($base);
    }
}
