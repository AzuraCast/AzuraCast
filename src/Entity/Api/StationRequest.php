<?php
namespace App\Entity\Api;

use App\Entity;
use Azura\Http\Router;
use OpenApi\Annotations as OA;
use Psr\Http\Message\UriInterface;

/**
 * @OA\Schema(type="object", schema="Api_StationRequest")
 */
class StationRequest implements ResolvableUrlInterface
{
    /**
     * Requestable ID unique identifier
     *
     * @OA\Property(example=1)
     * @var string
     */
    public $request_id;

    /**
     * URL to directly submit request
     *
     * @OA\Property(example="/api/station/1/request/1")
     * @var string
     */
    public $request_url;

    /**
     * Song
     *
     * @OA\Property
     * @var Song
     */
    public $song;

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
