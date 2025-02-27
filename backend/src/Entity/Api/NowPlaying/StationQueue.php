<?php

declare(strict_types=1);

namespace App\Entity\Api\NowPlaying;

use App\Entity\Api\Song;
use App\OpenApi;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Api_NowPlaying_StationQueue',
    required: ['*'],
    type: 'object'
)]
class StationQueue
{
    #[OA\Property(
        description: 'UNIX timestamp when the AutoDJ is expected to queue the song for playback.',
        example: OpenApi::SAMPLE_TIMESTAMP
    )]
    public int $cued_at = 0;

    #[OA\Property(
        description: 'UNIX timestamp when playback is expected to start.',
        example: OpenApi::SAMPLE_TIMESTAMP
    )]
    public ?int $played_at = null;

    #[OA\Property(
        description: 'Duration of the song in seconds',
        example: 180
    )]
    public float $duration = 0.0;

    #[OA\Property(
        description: 'Indicates the playlist that the song was played from, if available, or empty string if not.',
        example: 'Top 100'
    )]
    public ?string $playlist = null;

    #[OA\Property(
        description: 'Indicates whether the song is a listener request.',
    )]
    public bool $is_request = false;

    #[OA\Property]
    public Song $song;
}
