<?php

declare(strict_types=1);

namespace App\Entity\Api;

use App\Entity\Enums\PlaylistOrders;
use App\Entity\Enums\PlaylistSources;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Api_StationScheduleGroupMember',
    required: ['*'],
    type: 'object'
)]
final class StationScheduleGroupMember
{
    #[OA\Property(
        description: 'The unique identifier of the member playlist.',
        example: 1
    )]
    public int $id;

    #[OA\Property(
        description: 'The name of the member playlist.',
        example: 'Example Playlist'
    )]
    public string $name;

    #[OA\Property]
    public PlaylistSources $source;

    #[OA\Property]
    public PlaylistOrders $order;

    #[OA\Property(
        description: 'The weighting of this member within the group.',
        example: 3
    )]
    public int $weight;

    #[OA\Property(
        description: 'The number of songs or member playlists in this member, if applicable.',
        example: 25
    )]
    public ?int $count = null;

    #[OA\Property(
        description: 'The number of consecutive plays configured for this member.',
        example: 1
    )]
    public int $consecutive_plays;

    #[OA\Property(
        description: 'Whether the member plays through its full cycle before advancing.',
        example: false
    )]
    public bool $play_full_cycle;

    #[OA\Property(
        description: 'Whether the member playlist is enabled.',
        example: true
    )]
    public bool $is_enabled;
}
