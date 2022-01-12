<?php

declare(strict_types=1);

namespace App\Entity\Api\NowPlaying;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Api_NowPlaying_Live',
    type: 'object'
)]
class Live
{
    #[OA\Property(
        description: 'Whether the stream is known to currently have a live DJ.',
        example: false
    )]
    public bool $is_live = false;

    #[OA\Property(
        description: 'The current active streamer/DJ, if one is available.',
        example: 'DJ Jazzy Jeff'
    )
    ]
    public string $streamer_name = '';

    #[OA\Property(
        description: 'The start timestamp of the current broadcast, if one is available.',
        example: '1591548318'
    )]
    public ?int $broadcast_start = null;

    public function __construct(
        bool $is_live = false,
        string $streamer_name = '',
        ?int $broadcast_start = null
    ) {
        $this->is_live = $is_live;
        $this->streamer_name = $streamer_name;
        $this->broadcast_start = $broadcast_start;
    }
}
