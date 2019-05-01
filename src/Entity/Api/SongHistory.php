<?php

namespace App\Entity\Api;

use App\Entity;
use Azura\Http\Router;
use OpenApi\Annotations as OA;

/**
 * @OA\Schema(type="object", schema="Api_SongHistory")
 */
class SongHistory implements ResolvableUrlInterface
{
    /**
     * Song history unique identifier
     *
     * @OA\Property
     * @var int
     */
    public $sh_id;

    /**
     * UNIX timestamp when playback started.
     *
     * @OA\Property(example=SAMPLE_TIMESTAMP)
     * @var int
     */
    public $played_at;

    /**
     * Duration of the song in seconds
     *
     * @OA\Property(example=180)
     * @var int
     */
    public $duration;

    /**
     * Indicates the playlist that the song was played from, if available, or empty string if not.
     *
     * @OA\Property(example="Top 100")
     * @var string
     */
    public $playlist;

    /**
     * Indicates whether the song is a listener request.
     *
     * @OA\Property
     * @var bool
     */
    public $is_request;

    /**
     * Song
     *
     * @OA\Property()
     * @var Song
     */
    public $song;

    /**
     * Re-resolve any Uri instances to reflect base URL changes.
     *
     * @param Router $router
     */
    public function resolveUrls(Router $router): void
    {
        if ($this->song instanceof ResolvableUrlInterface) {
            $this->song->resolveUrls($router);
        }
    }
}
