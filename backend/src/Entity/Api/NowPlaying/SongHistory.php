<?php

declare(strict_types=1);

namespace App\Entity\Api\NowPlaying;

use App\Entity\Api\ResolvableUrlInterface;
use App\Entity\Api\Song;
use App\OpenApi;
use OpenApi\Attributes as OA;
use Psr\Http\Message\UriInterface;

#[OA\Schema(
    schema: 'Api_NowPlaying_SongHistory',
    type: 'object'
)]
class SongHistory implements ResolvableUrlInterface
{
    #[OA\Property(
        description: 'Song history unique identifier'
    )]
    public int $sh_id;

    #[OA\Property(
        description: 'UNIX timestamp when playback started.',
        example: OpenApi::SAMPLE_TIMESTAMP
    )]
    public int $played_at = 0;

    #[OA\Property(
        description: 'Duration of the song in seconds',
        example: 180
    )]
    public int $duration = 0;

    #[OA\Property(
        description: 'Indicates the playlist that the song was played from, if available, or empty string if not.',
        example: 'Top 100'
    )]
    public ?string $playlist = null;

    #[OA\Property(
        description: 'Indicates the current streamer that was connected, if available, or empty string if not.',
        example: 'Test DJ'
    )]
    public ?string $streamer = null;

    #[OA\Property(
        description: 'Indicates whether the song is a listener request.',
    )]
    public bool $is_request = false;

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
