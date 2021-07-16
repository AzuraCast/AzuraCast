<?php

declare(strict_types=1);

namespace App\Entity\Api;

use OpenApi\Annotations as OA;

/**
 * @OA\Schema(type="object", schema="Api_StationPlaylistQueue")
 */
class StationPlaylistQueue
{
    /**
     * ID of the StationPlaylistMedia record associating this track with the playlist
     *
     * @OA\Property(example=1)
     * @var int|null
     */
    public ?int $spm_id = null;

    /**
     * ID of the StationPlaylistMedia record associating this track with the playlist
     *
     * @OA\Property(example=1)
     * @var int
     */
    public int $media_id;

    /**
     * The song's 32-character unique identifier hash
     *
     * @OA\Property(example="9f33bbc912c19603e51be8e0987d076b")
     * @var string
     */
    public string $song_id;

    /**
     * The song artist.
     *
     * @OA\Property(example="Chet Porter")
     * @var string
     */
    public string $artist = '';

    /**
     * The song title.
     *
     * @OA\Property(example="Aluko River")
     * @var string
     */
    public string $title = '';
}
