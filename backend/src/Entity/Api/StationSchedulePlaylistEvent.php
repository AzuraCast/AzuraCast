<?php

declare(strict_types=1);

namespace App\Entity\Api;

use App\Entity\Enums\PlaylistOrders;
use App\Entity\Enums\PlaylistRemoteTypes;
use App\Entity\Enums\PlaylistSources;
use App\Entity\Enums\PlaylistTypes;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Api_StationSchedulePlaylistEvent',
    required: ['*'],
    type: 'object'
)]
final class StationSchedulePlaylistEvent
{
    #[OA\Property(
        description: 'The unique identifier of the playlist.',
        example: 1
    )]
    public int $id;

    #[OA\Property(
        description: 'The playlist name representing the event title.',
        example: 'Example Playlist'
    )]
    public string $title;

    #[OA\Property(
        description: 'The type of this schedule event.',
        enum: ['playlist'],
        example: 'playlist'
    )]
    public string $type = 'playlist';

    #[OA\Property(
        description: 'The start time of this schedule event, in ISO 8601 format.',
        example: '2020-02-19T03:00:00-06:00'
    )]
    public string $start;

    #[OA\Property(
        description: 'The end time of this schedule event, in ISO 8601 format.',
        example: '2020-02-19T05:00:00-06:00'
    )]
    public string $end;

    #[OA\Property(
        description: 'The API URL used to edit the underlying playlist.',
        example: '/api/station/1/playlist/1'
    )]
    public string $edit_url;

    #[OA\Property]
    public PlaylistSources $source;

    #[OA\Property]
    public PlaylistOrders $order;

    #[OA\Property]
    public PlaylistTypes $playlist_type;

    #[OA\Property(example: 0)]
    public int $play_per_songs;

    #[OA\Property(example: 0)]
    public int $play_per_minutes;

    #[OA\Property(example: 0)]
    public int $play_per_hour_minute;

    #[OA\Property(example: 3)]
    public int $weight;

    #[OA\Property(example: false)]
    public bool $is_jingle;

    #[OA\Property(example: false)]
    public bool $include_in_on_demand;

    #[OA\Property(example: false)]
    public bool $avoid_duplicates;

    #[OA\Property(
        description: 'True if this playlist is a group member whose schedule window is not covered by an active '
            . 'ancestor group schedule, so it will not play during this event.',
        readOnly: true,
        example: false
    )]
    public bool $has_group_schedule_conflict = false;

    /**
     * @var StationScheduleGroupMember[]
     */
    #[OA\Property(
        type: 'array',
        items: new OA\Items(ref: StationScheduleGroupMember::class)
    )]
    public array $members = [];

    #[OA\Property(
        description: 'The number of songs in the playlist, if it is a song-based playlist.',
        example: 25
    )]
    public ?int $num_songs = null;

    #[OA\Property(
        description: 'The total length of the playlist in seconds, if it is a song-based playlist.',
        example: 3600
    )]
    public ?float $total_length = null;

    #[OA\Property(
        description: 'The remote URL, if this is a remote URL playlist.',
        example: 'https://example.com/stream'
    )]
    public ?string $remote_url = null;

    #[OA\Property]
    public ?PlaylistRemoteTypes $remote_type = null;
}
