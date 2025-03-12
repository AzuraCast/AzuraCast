<?php

declare(strict_types=1);

namespace App\Entity\Api\NowPlaying;

use App\Entity\Api\ResolvableUrl;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Api_NowPlaying_Live',
    required: ['*'],
    type: 'object'
)]
final class Live
{
    #[OA\Property(
        description: 'Whether the stream is known to currently have a live DJ.',
        example: false
    )]
    public bool $is_live = false;

    #[OA\Property(
        description: 'The current active streamer/DJ, if one is available.',
        example: 'DJ Jazzy Jeff'
    )]
    public string $streamer_name = '';

    #[OA\Property(
        description: 'The start timestamp of the current broadcast, if one is available.',
        example: '1591548318'
    )]
    public ?int $broadcast_start = null;

    #[OA\Property(
        description: 'URL to the streamer artwork (if available).',
        type: 'string',
        example: 'https://picsum.photos/1200/1200',
        nullable: true
    )]
    public ?ResolvableUrl $art = null;
}
