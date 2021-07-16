<?php

declare(strict_types=1);

namespace App\Entity\Api;

use OpenApi\Annotations as OA;
use Psr\Http\Message\UriInterface;

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
    public int $sh_id;

    /**
     * UNIX timestamp when playback started.
     *
     * @OA\Property(example=SAMPLE_TIMESTAMP)
     * @var int
     */
    public int $played_at = 0;

    /**
     * Duration of the song in seconds
     *
     * @OA\Property(example=180)
     * @var int
     */
    public int $duration = 0;

    /**
     * Indicates the playlist that the song was played from, if available, or empty string if not.
     *
     * @OA\Property(example="Top 100")
     * @var string|null
     */
    public ?string $playlist = null;

    /**
     * Indicates the current streamer that was connected, if available, or empty string if not.
     *
     * @OA\Property(example="Test DJ")
     * @var string|null
     */
    public ?string $streamer = null;

    /**
     * Indicates whether the song is a listener request.
     *
     * @OA\Property
     * @var bool
     */
    public bool $is_request = false;

    /**
     * Song
     *
     * @OA\Property()
     * @var Song
     */
    public Song $song;

    /**
     * Re-resolve any Uri instances to reflect base URL changes.
     *
     * @param UriInterface $base
     */
    public function resolveUrls(UriInterface $base): void
    {
        if ($this->song instanceof ResolvableUrlInterface) {
            $this->song->resolveUrls($base);
        }
    }
}
