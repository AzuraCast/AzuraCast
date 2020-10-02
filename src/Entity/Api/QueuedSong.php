<?php
namespace App\Entity\Api;

use OpenApi\Annotations as OA;

/**
 * @OA\Schema(type="object", schema="Api_QueuedSong")
 */
class QueuedSong extends SongHistory
{
    /**
     * UNIX timestamp when the item was cued for playback.
     *
     * @OA\Property(example=SAMPLE_TIMESTAMP)
     * @var int
     */
    public int $cued_at;

    /**
     * Custom AutoDJ playback URI, if it exists.
     *
     * @OA\Property(example="")
     * @var string|null
     */
    public ?string $autodj_custom_uri = null;

    /**
     * @OA\Property(
     *     @OA\Items(
     *         type="string",
     *         example="http://localhost/api/stations/1/queue/1"
     *     )
     * )
     * @var array
     */
    public array $links = [];
}
