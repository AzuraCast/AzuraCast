<?php

declare(strict_types=1);

namespace App\Entity\Api;

use App\Entity\Api\NowPlaying\SongHistory;
use App\Traits\LoadFromParentObject;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Api_DetailedSongHistory',
    type: 'object'
)]
final class DetailedSongHistory extends SongHistory
{
    use LoadFromParentObject;

    #[OA\Property(
        description: 'Number of listeners when the song playback started.',
        example: 94
    )]
    public int $listeners_start = 0;

    #[OA\Property(
        description: 'Number of listeners when song playback ended.',
        example: 105
    )]
    public int $listeners_end = 0;

    #[OA\Property(
        description: 'The sum total change of listeners between the song\'s start and ending.',
        example: 11
    )]
    public int $delta_total = 0;

    #[OA\Property(
        description: 'Whether the entry is visible on public playlists.',
        example: true
    )]
    public bool $is_visible = true;
}
