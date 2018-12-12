<?php

namespace App\Entity\Api;

use App\Entity;
use Azura\Http\Router;

/**
 * @OA\Schema(type="object")
 */
class QueuedSong extends SongHistory
{
    /**
     * UNIX timestamp when the item was cued for playback.
     *
     * @OA\Property(example=SAMPLE_TIMESTAMP)
     * @var int
     */
    public $cued_at;

    /**
     * Custom AutoDJ playback URI, if it exists.
     *
     * @OA\Property(example="")
     * @var string
     */
    public $autodj_custom_uri;

    /**
     * @OA\Property(
     *     @OA\Items(
     *         type="string",
     *         example="http://localhost/api/stations/1/queue/1"
     *     )
     * )
     * @var array
     */
    public $links = [];
}
