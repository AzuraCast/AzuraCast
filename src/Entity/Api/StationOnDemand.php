<?php

declare(strict_types=1);

namespace App\Entity\Api;

use OpenApi\Annotations as OA;
use Psr\Http\Message\UriInterface;

/**
 * @OA\Schema(type="object", schema="Api_StationOnDemand")
 */
class StationOnDemand implements ResolvableUrlInterface
{
    /**
     * Track ID unique identifier
     *
     * @OA\Property(example=1)
     * @var string
     */
    public string $track_id;

    /**
     * URL to download/play track.
     *
     * @OA\Property(example="/api/station/1/ondemand/download/1")
     * @var string
     */
    public string $download_url;

    /**
     * Song
     *
     * @OA\Property
     * @var Song
     */
    public Song $media;

    /**
     * @OA\Property
     * @var string
     */
    public string $playlist;

    /**
     * Re-resolve any Uri instances to reflect base URL changes.
     *
     * @param UriInterface $base
     */
    public function resolveUrls(UriInterface $base): void
    {
        $this->media->resolveUrls($base);
    }
}
